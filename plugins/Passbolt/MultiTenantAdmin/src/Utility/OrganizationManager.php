<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace Passbolt\MultiTenantAdmin\Utility;

use App\Model\Entity\AuthenticationToken;
use App\Model\Entity\Role;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\Exception;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\ModelAwareTrait;
use Cake\Utility\Hash;
use Cake\View\ViewVarsTrait;
use Migrations\Migrations;
use Passbolt\MultiTenantAdmin\Model\Table\OrganizationsTable;

class OrganizationManager
{
    // Will use models.
    use ModelAwareTrait;

    // Used to manipulate views.
    use ViewVarsTrait;

    // Slug of the organization.
    public $slug = '';

    // Database name.
    public $databaseName = '';

    // Fingerprint of organization key.
    public $fingerprint = '';

    /**
     * OrganizationManager constructor.
     *
     * @param string $slug slug
     */
    public function __construct($slug = '')
    {
        $this->slug = $slug;
        $this->databaseName = str_replace('-', '_', $slug);

        $this->Organizations = $this->loadModel('Organizations');
    }

    /**
     * Add an organization.
     * @return void
     * @throws \Exception
     */
    public function add()
    {
        // Check if the organization exists already in the configuration or database.
        if ($this->_checkExist()) {
            throw new Exception(__('The organization already exists'));
        }

        // Build data.
        $data = [
            'organization' => [
                'slug' => $this->slug,
                'plan' => 'trial',
                'max_users' => 2,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];

        $organization = $this->_buildAndValidateOrganizationEntity($data['organization']);
        $errors = $organization->getErrors();
        if (!empty($errors)) {
            throw new \Exception(__('Could not validate organization data.'));
        }

        if (!$this->Organizations->save($organization)) {
            $errors = $organization->getErrors();
            if (!empty($errors)) {
                throw new \Exception(__('Could not save organization.'));
            }
        }

        $this->_createOrgDirectory();
        $this->_createDatabase();
        $this->_createGpgServerKeys();
        $this->_createConfigurationFile();
        $this->_loadOrgConfiguration();
        $this->_createSchema();
    }

    /**
     * Build and validate organization entity.
     *
     * @param array $data data
     *
     * @return Passbolt\MultiTenantAdmin\Model\Entity $organization organization entity
     */
    protected function _buildAndValidateOrganizationEntity($data)
    {
        if(isset($data['organization']['slug'])) {
            $data['organization']['slug'] = strtolower($data['organization']['slug']);
        }

        // Build entity and perform basic check.
        $organization = $this->Organizations->newEntity(
            $data,
            [
                'accessibleFields' => [
                    'slug' => true,
                    'plan' => true,
                    'max_users' => true,
                ],
            ]
        );

        return $organization;
    }

    /**
     * Migrate schema.
     * @return bool
     */
    public function migrate() {
        $this->_loadOrgConfiguration();
        $migrations = new Migrations();
        // Do not remove this line. It forces the migration plugin to clear its cache regarding the conf.
        $migrations->status();
        $migrated = $migrations->migrate();

        return $migrated;
    }

    /**
     * Check if organization already exists.
     * @return bool
     */
    protected function _checkExist()
    {
        // Check if database exists.
        $connection = ConnectionManager::get('default');
        $databaseExist = $connection->execute("SHOW DATABASES LIKE '{$this->databaseName}'")->fetchAll();
        $databaseExist = count($databaseExist) > 0;

        // Check if directory exists.
        $confExist = file_exists(CONFIG . 'Org' . DS . $this->slug);

        return $databaseExist || $confExist;
    }

    /**
     * Create GPG server keys.
     * @return void
     */
    protected function _createGpgServerKeys()
    {
        $fingerprint = $this->_generateGpgKey([
            'name' => $this->slug,
            'email' => 'admin@passbolt.com',
            'comment' => ''
        ]);
        $this->_exportArmoredKeys($fingerprint);
        $this->fingerprint = $fingerprint;
    }

    /**
     * Create organization top level directory.
     * @return void
     */
    protected function _createOrgDirectory()
    {
        // Create directory
        mkdir($this->_getConfigurationPath());
    }

    /**
     * Get configuration path for the organization.
     * @return string
     */
    protected function _getConfigurationPath()
    {
        return Configure::read('passbolt.multiTenant.configDir') . DS . $this->slug;
    }

    /**
     * Create configuration file for the given organization.
     * @return void
     */
    protected function _createConfigurationFile()
    {
        $orgName = $this->slug;

        // Read existing connection parameters.
        Configure::load('passbolt', 'default', true);
        $existingDbConfig = ConnectionManager::getConfig('default');
        $config = [
            'meta' => [
                'title' => $orgName,
            ],
            'options' => [
                'full_base_url' => Configure::read('App.fullBaseUrl') . '/' . $orgName,
                'public_registration' => false,
                'force_ssl' => false,
            ],
            'database' => [
                'host' => $existingDbConfig['host'],
                'port' => $existingDbConfig['port'],
                'username' => $existingDbConfig['username'],
                'password' => $existingDbConfig['password'],
                'database' => $this->databaseName,
            ],
            'email' => [
                'host' => Configure::read('EmailTransport.default.host'),
                'port' => Configure::read('EmailTransport.default.port'),
                'username' => Configure::read('EmailTransport.default.username'),
                'password' => Configure::read('EmailTransport.default.password'),
                'tls' => Configure::read('EmailTransport.default.tls'),
                'sender_email' => key(Configure::read('Email.default.from')),
                'sender_name' => Configure::read('Email.default.from')[key(Configure::read('Email.default.from'))]
            ],
            'gpg' => [
                'keyring' => $this->_getGnupgKeyringPath(),
                'fingerprint' => $this->fingerprint,
                'public' => $this->_getConfigurationPath() . DS . 'gpg' . DS . 'serverkey.asc',
                'private' => $this->_getConfigurationPath() . DS . 'gpg' . DS . 'serverkey_private.asc',
            ]
        ];

        // Create directory
        $confPath = $this->_getConfigurationPath();
        $this->set(['config' => $config]);
        $configView = $this->createView();
        $configView->plugin = 'Passbolt/MultiTenantAdmin';
        $contents = $configView->render('Config/passbolt', 'ajax');
        $contents = "<?php\n$contents";
        file_put_contents($confPath . DS . 'passbolt.php', $contents);
    }

    /**
     * Create database for the given organization.
     * @return void
     */
    protected function _createDatabase()
    {
        Configure::load('passbolt', 'default', true);

        $connection = ConnectionManager::get('default');
        $sql = "CREATE DATABASE {$this->databaseName}";
        $connection->execute($sql);
    }

    /**
     * Get Gnupg keyring path for the organization.
     * @return string
     */
    protected function _getGnupgKeyringPath()
    {
        return $this->_getConfigurationPath() . DS . 'gpg' . DS . '.gnupg';
    }

    /**
     * Create keyring for the organization
     * @return void
     */
    protected function _createKeyring()
    {
        $gpgKeyPath = $this->_getGnupgKeyringPath();
        mkdir($gpgKeyPath, 0700, true);
        // Create keyring.
        putenv('GNUPGHOME=' . $this->_getGnupgKeyringPath());
        exec('gpg --list-keys');
    }

    /**
     * Generate GPG key based on data provided.
     * @param array $keyData key data
     *
     * @return mixed
     * TODO: move this as part of GPG utility.
     */
    protected function _generateGpgKey($keyData)
    {
        $this->_createKeyring();
        // Generate key.
        $generateKeyCmd = $this->_generateKeyCmdV2($keyData);

        $cmdOutput = "";
        exec($generateKeyCmd, $cmdOutput, $cmdRes);

        if ($cmdRes !== 0) {
            throw new Exception("Could not generate GPG key");
        }

        $res = gnupg_init();
        $info = gnupg_keyinfo($res, $keyData['email']);

        if (empty($info) || !isset($info[0]['subkeys'][0]['fingerprint'])) {
            throw new Exception("Could not retrieve the generated key");
        }

        // There can be several keys that match the email id (already present in the keyring before).
        // We need to identify the last one.
        $correspondingFingerprints = Hash::combine($info, '{n}.subkeys.0.timestamp', '{n}.subkeys.0.fingerprint');
        krsort($correspondingFingerprints);
        $lastGeneratedFingerprint = reset($correspondingFingerprints);

        return $lastGeneratedFingerprint;
    }

    /**
     * Export armored keys in the config folder based on the fingerprint provided.
     * @param string $fingerprint key fingerprint
     * @throws Exception when the key cannot be exported
     * @return void
     * TODO: move this as part of GPG utility.
     */
    protected function _exportArmoredKeys($fingerprint)
    {
        $gpgKeyPath = $this->_getConfigurationPath() . DS . 'gpg';
        $publicKeyPath = $gpgKeyPath . DS . 'serverkey.asc';
        $privateKeyPath = $gpgKeyPath . DS . 'serverkey_private.asc';

        $cmd = "gpg --armor --export $fingerprint > $publicKeyPath";
        exec($cmd, $cmdOutput, $cmdRes);
        if ($cmdRes !== 0) {
            throw new Exception("Could not export public key");
        }

        $cmd = "gpg --armor --export-secret-keys $fingerprint > $privateKeyPath";
        exec($cmd, $cmdOutput, $cmdRes);
        if ($cmdRes !== 0) {
            throw new Exception("Could not export private key");
        }
    }

    /**
     * Generate a key pair using system GPG binary V2.
     * @param array $keyData key data as provided by form
     * @return string command
     * TODO: move this as part of GPG utility.
     */
    protected function _generateKeyCmdV2($keyData)
    {
        $cmd = "gpg --batch --no-tty --gen-key <<EOF
Key-Type: default
Key-Length: 2048
Subkey-Type: default
Subkey-Length: 2048
Name-Real: {$keyData['name']}" . (isset($keyData['comment']) && !empty($keyData['comment']) ? "
Name-Comment: {$keyData['comment']}" : '') . "
Name-Email: {$keyData['email']}
Expire-Date: 0
%no-protection
%commit
EOF";

        return $cmd;
    }

    /**
     * Reload configuration sequence from scratch to include the new changes.
     * @return void
     */
    protected function _loadOrgConfiguration()
    {
        Configure::config('org', new PhpConfig($this->_getConfigurationPath() . DS));
        Configure::load('app', 'default', false);
        Configure::load('passbolt', 'org', true);

        ConnectionManager::drop('default');
        ConnectionManager::drop('test');
        ConnectionManager::setConfig(Configure::consume('Datasources'));
    }

    /**
     * Create schema in db.
     * @return bool
     */
    protected function _createSchema()
    {
        $migrations = new Migrations();
        $migrated = $migrations->migrate();

        return $migrated;
    }

    /**
     * Add admin user to the newly created organization.
     * @param array $data user data
     *
     * @return array user and token
     * @throws Exception
     */
    public function addAdminUser($data)
    {
        $this->_loadOrgConfiguration();

        $this->loadModel('Roles');
        $this->loadModel('Users');
        $this->loadModel('AuthenticationTokens');

        $roleId = $this->Roles->getIdByName(Role::ADMIN);
        if (empty($roleId)) {
            throw new Exception('Cannot find role');
        }

        // Force role and build entity.
        $data['role_id'] = $roleId;
        $user = $this->Users->buildEntity($data, Role::ADMIN);

        $errors = $user->getErrors();
        if (empty($errors)) {
            $this->Users->checkRules($user);
            $errors = $user->getErrors();
        }
        if (!empty($errors)) {
            throw new Exception('Cannot validate user');
        }

        $saved = $this->Users->save($user, ['checkrules' => false]);
        if (!$saved) {
            throw new Exception('Cannot save user');
        }

        $token = $this->AuthenticationTokens->generate($user->id, AuthenticationToken::TYPE_REGISTER);

        return ['user' => $user, 'token' => $token];
    }
}
