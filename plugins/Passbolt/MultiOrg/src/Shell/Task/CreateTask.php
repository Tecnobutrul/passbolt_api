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
namespace Passbolt\MultiOrg\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;
use Cake\View\ViewVarsTrait;
use Migrations\Migrations;
use Cake\Core\Configure\Engine\PhpConfig;

/**
 * Create Org shell command.
 */
class CreateTask extends Shell
{
    use ViewVarsTrait;

    public $fingerprint = '';

    public $options = [
        'orgName' => '',
    ];

    /**
     * Gets the option parser instance and configures it.
     *
     * By overriding this method you can configure the ConsoleOptionParser before returning it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     * @link https://book.cakephp.org/3.0/en/console-and-shells.html#configuring-options-and-generating-help
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription(__('Create an organization.'));
        $parser->addOption('name', [
            'help' => __('Organization name')
        ]);

        return $parser;
    }

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main()
    {
        $this->_extractOptions();

        if ($this->_checkExist()) {
            return $this->err(__('The org name already exists'));
        }

        $this->_createOrgDirectory();
        $this->_createDatabase();
        $this->_createGpgServerKeys();
        $this->_createConfigurationFile();
        $this->_loadOrgConfiguration();
        $this->_createSchema();

        return true;
    }

    /**
     * Check if organization already exists.
     * @return bool
     */
    public function _checkExist()
    {
        $orgName = $this->options['orgName'];

        // Check if database exists.
        $connection = ConnectionManager::get('default');
        $databaseExist = $connection->execute("SHOW DATABASES LIKE '$orgName'")->fetchAll();
        $databaseExist = count($databaseExist) > 0;

        // Check if directory exists.
        $confExist = file_exists(CONFIG . 'Org' . DS . $orgName);

        return $databaseExist || $confExist;
    }

    /**
     * Extract options provided and set them in an options variable.
     */
    protected function _extractOptions() {
        $this->options['orgName'] = $this->param('name');
    }

    /**
     * Create GPG server keys.
     */
    protected function _createGpgServerKeys() {
        $fingerprint = $this->_generateGpgKey([
            'name' => $this->options['orgName'],
            'email' => 'admin@passbolt.com',
            'comment' => ''
        ]);
        $this->_exportArmoredKeys($fingerprint);
        $this->fingerprint = $fingerprint;
    }

    /**
     * Create organization top level directory.
     */
    protected function _createOrgDirectory() {
        // Create directory
        mkdir($this->_getConfigurationPath());
    }

    /**
     * Get configuration path for the organization.
     * @return string
     */
    protected function _getConfigurationPath()
    {
        return Configure::read('passbolt.multiOrg.configDir') . DS . $this->options['orgName'];
    }

    /**
     * Create configuration file for the given organization.
     */
    protected function _createConfigurationFile() {
        $orgName = $this->options['orgName'];

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
                'database' => $orgName,
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
        $configView->plugin = 'Passbolt/MultiOrg';
        $contents = $configView->render('Config/passbolt', 'ajax');
        $contents = "<?php\n$contents";
        file_put_contents($confPath . DS . 'passbolt.php', $contents);
    }

    /**
     * Create database for the given organization.
     */
    protected function _createDatabase() {
        Configure::load('passbolt', 'default', true);

        $connection = ConnectionManager::get('default');
        $sql = "CREATE DATABASE {$this->options['orgName']}";
        $connection->execute($sql);
    }

    /**
     * Get Gnupg keyring path for the organization.
     * @return string
     */
    protected function _getGnupgKeyringPath() {
        return  $this->_getConfigurationPath() . DS . 'gpg' . DS . '.gnupg';
    }

    /**
     * Create keyring for the organization
     */
    protected function _createKeyring() {
        $gpgKeyPath =  $this->_getGnupgKeyringPath();
        mkdir($gpgKeyPath, 0700, true);
        // Create keyring.
        putenv('GNUPGHOME=' . $this->_getGnupgKeyringPath());
        exec('gpg --list-keys');
    }

    /**
     * Generate GPG key based on data provided.
     * @param $keyData
     *
     * @return mixed
     * TODO: move this as part of GPG utility.
     */
    protected function _generateGpgKey($keyData) {
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
        $gpgKeyPath =  $this->_getConfigurationPath() . DS . 'gpg';
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
    protected function _createSchema() {
        $migrations = new Migrations();
        $migrated = $migrations->migrate();

        return $migrated;
    }
}