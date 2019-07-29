<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.11.0
 */
namespace Passbolt\CloudSubscription\Utility;

use App\Error\Exception\ValidationException;
use App\Model\Entity\Role;
use App\Model\Table\OrganizationSettingsTable;
use App\Model\Table\UsersTable;
use App\Utility\UserAccessControl;
use App\Utility\UuidFactory;
use Cake\Chronos\Date;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\InternalErrorException;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

class CloudSubscriptionSettings
{
    const NAMESPACE = 'cloudSubscription';

    /**
     * @var bool
     */
    private $isTrial;

    /**
     * @var
     */
    private $expiryDate;

    /**
     * @var OrganizationSettingsTable $OrganizationSettings
     */
    private $OrganizationSettings;

    /**
     * @var UserAccessControl $uac
     */
    private $uac;

    /**
     * CloudSubscriptionSettings constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->OrganizationSettings = TableRegistry::getTableLocator()->get('OrganizationSettings');
        $msg = __('Invalid cloud subscription settings.') . ' ';

        // Is trial boolean and expiry date init
        if (empty($settings['isTrial']) && !is_bool($settings['isTrial'])) {
            throw new InternalErrorException($msg  . __('Trial status missing.'));
        } else {
            $this->isTrial = $settings['isTrial'];
        }
        if (empty($settings['expiryDate'])) {
            $this->expiryDate = null;
        } else {
            try {
                $this->expiryDate = new FrozenTime($settings['expiryDate']);
            } catch (\Exception $exception) {
                throw new InternalErrorException($msg  . __('Expiry date invalid.'));
            }
        }

        // Define user access control object
        // We impersonate the first "most likely active" admin
        // otherwise we go with a random user id that does not exist
        /** @var UsersTable $Users */
        $Users = TableRegistry::getTableLocator()->get('Users');
        $admin = $Users->find()
            ->where(['Roles.name' => Role::ADMIN])
            ->order(['Users.deleted' => 'ASC', 'Users.active' => 'DESC', 'Users.created' => 'ASC'])
            ->contain(['Roles'])
            ->first();
        if (empty($admin)) {
            throw new InternalErrorException($msg  . __('No admin found.'));
        } else {
            $this->uac = new UserAccessControl(Role::ADMIN, UuidFactory::uuid('user.cloud.system'));
        }
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        if ($this->expiryDate === null) {
            return false;
        } else {
            return $this->expiryDate->isPast();
        }
    }

    /**
     * @return bool
     */
    public function isTrial()
    {
        return $this->isTrial;
    }

    /**
     * Get cloud subscription configuration information
     *
     * @throws RecordNotFoundException When there is no first record.
     */
    public static function get()
    {
        try {
            /** @var OrganizationSettingsTable $OrganizationSettings */
            $OrganizationSettings = TableRegistry::getTableLocator()->get('OrganizationSettings');
            $setting = $OrganizationSettings->getFirstSettingOrFail(static::NAMESPACE);
        } catch (RecordNotFoundException $exception) {
            throw new RecordNotFoundException(__('No cloud subscription found.'));
        }
        $settings = json_decode($setting['value'], true);
        if ($settings === null) {
            throw new RecordNotFoundException(__('No valid cloud subscription found.'));
        }

        return new CloudSubscriptionSettings($settings);
    }

    /**
     * Serialize settings to Json
     *
     * @throws InternalErrorException if settings cannot be serialized
     * @return string
     */
    public function toJson()
    {
        $json = json_encode([
            'isTrial' => $this->isTrial(),
            'expiryDate' => $this->expiryDate->toUnixString()
        ]);
        if ($json === false) {
            throw new InternalErrorException(__('Could not serialize cloud subscription settings.'));
        }
        return $json;
    }

    /**
     * Save the cloud subscription information
     *
     * @throws InternalErrorException if save operation failed
     * @return void
     */
    public function save()
    {
        try {
            $this->OrganizationSettings->createOrUpdateSetting(self::NAMESPACE, $this->toJson(), $this->uac);
        } catch(Exception $exception) {
            $msg = __('Could not save subscription settings.') . ' ' . $exception->getMessage();
            throw new InternalErrorException($msg);
        }
    }
}