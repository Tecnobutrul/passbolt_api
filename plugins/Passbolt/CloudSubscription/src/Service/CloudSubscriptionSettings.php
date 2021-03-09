<?php
declare(strict_types=1);

/**
 * Passbolt Cloud
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\CloudSubscription\Service;

use App\Model\Entity\Role;
use App\Utility\UserAccessControl;
use App\Utility\UuidFactory;
use Cake\Chronos\Date;
use Cake\Core\Exception\Exception;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\TableRegistry;
use Passbolt\CloudSubscription\Form\CloudSubscriptionSettingsForm;

class CloudSubscriptionSettings
{
    public const NAMESPACE = 'cloudSubscription';

    /**
     * Supported statuses
     */
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRIAL = 'trial';

    public const TRIAL_DURATION = '+14 days';
    public const REDEMPTION_DURATION = '+14 days';

    /**
     * @var string $status, any STATUS_*
     */
    private $status;

    /**
     * @var \Cake\Chronos\Date $expiryDate optional
     */
    private $expiryDate;

    /**
     * @var \App\Model\Table\OrganizationSettingsTable $OrganizationSettings org settings table
     */
    private $OrganizationSettings;

    /**
     * @var \App\Utility\UserAccessControl $uac user access control
     */
    private $uac;

    /**
     * CloudSubscriptionSettings constructor.
     *
     * @param array $settings settings
     * @param bool $validate or not
     * @throws \Cake\Http\Exception\InternalErrorException if $settings are invalid
     */
    public function __construct(array $settings, bool $validate = true)
    {
        $this->set($settings, $validate);

        // Organization settings table helper
        $this->OrganizationSettings = TableRegistry::getTableLocator()->get('OrganizationSettings');

        // Define user access control object
        // We impersonate the first "most likely active" admin
        // otherwise we go with a random user id that does not exist
        /** @var \App\Model\Table\UsersTable $Users */
        $Users = TableRegistry::getTableLocator()->get('Users');
        $admin = $Users->find()
            ->where(['Roles.name' => Role::ADMIN])
            ->order(['Users.deleted' => 'ASC', 'Users.active' => 'DESC', 'Users.created' => 'ASC'])
            ->contain(['Roles'])
            ->first();
        if (empty($admin)) {
            $this->uac = new UserAccessControl(Role::ADMIN, UuidFactory::uuid('user.cloud.system'));
        } else {
            $this->uac = new UserAccessControl(Role::ADMIN, $admin->id);
        }
    }

    /**
     * Set members data or throw exception
     *
     * @param array $settings settings
     * @param bool $validate or not
     * @throws \App\Error\Exception\CustomValidationException if does not validate
     * @return void
     */
    public function set(array $settings, bool $validate = true)
    {
        if ($validate) {
            self::validate($settings);
        }
        $this->status = $settings['status'];
        $this->expiryDate = new Date($settings['expiryDate']);
    }

    /**
     * @return bool true if subscription is expired
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
     * @return bool true if subscription is in trial
     */
    public function isTrial()
    {
        return $this->status === self::STATUS_TRIAL;
    }

    /**
     * @return bool true if org is archived
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return bool if org subscription is overdue
     */
    public function isDisabled()
    {
        return $this->status === self::STATUS_DISABLED;
    }

    /**
     * @return bool if org is schedule for deletion
     */
    public function isDeleted()
    {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Get cloud subscription configuration information
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When there is no first record.
     * @throws \Cake\Http\Exception\InternalErrorException if $settings are invalid
     * @return \Passbolt\CloudSubscription\Service\CloudSubscriptionSettings
     */
    public static function get()
    {
        try {
            /** @var \App\Model\Table\OrganizationSettingsTable $OrganizationSettings */
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
     * Get supported status names list
     *
     * @return array list of supported statuses
     */
    public static function getSupportedStatusNames()
    {
        return [
            self::STATUS_DISABLED,
            self::STATUS_DELETED,
            self::STATUS_ACTIVE,
            self::STATUS_TRIAL,
        ];
    }

    /**
     * Serialize settings to Json
     *
     * @throws \Cake\Http\Exception\InternalErrorException if settings cannot be serialized
     * @return string
     */
    public function toJson()
    {
        $json = json_encode($this->toArray());
        if ($json === false) {
            throw new InternalErrorException(__('Could not serialize cloud subscription settings.'));
        }

        return $json;
    }

    /**
     * Get current subscription settings as an array
     *
     * @return array ['status' => <string>, 'expiryDate' => <unix time string>]
     */
    public function toArray()
    {
        return [
            'status' => $this->status,
            'expiryDate' => $this->expiryDate->toUnixString(),
        ];
    }

    /**
     * Save the cloud subscription information
     *
     * @throws \Cake\Http\Exception\InternalErrorException if save operation failed
     * @return void
     */
    public function save()
    {
        try {
            $this->OrganizationSettings->createOrUpdateSetting(self::NAMESPACE, $this->toJson(), $this->uac);
        } catch (Exception $exception) {
            $msg = __('Could not save subscription settings.') . ' ' . $exception->getMessage();
            throw new InternalErrorException($msg);
        }
    }

    /**
     * Validate subscription status settings
     *
     * @param array $settings settings
     * @throws \App\Error\Exception\CustomValidationException if $settings do not validate
     * @return void
     */
    public static function validate(array $settings)
    {
        $form = new CloudSubscriptionSettingsForm();
        $form->execute($settings);
    }

    /**
     * Update or create subscription status settings
     *
     * @param array $settings settings
     * @throws \App\Error\Exception\CustomValidationException if $settings do not validate
     * @throws \Cake\Http\Exception\InternalErrorException if save operation failed
     * @return void
     */
    public static function updateOrCreate(array $settings)
    {
        self::validate($settings);
        try {
            $instance = self::get();
        } catch (\Exception $exception) {
            // if existing settings are not present or invalid
            $instance = new CloudSubscriptionSettings($settings, false);
        }

        $instance->set($settings, false);
        $instance->save();
    }
}
