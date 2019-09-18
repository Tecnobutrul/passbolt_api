<?php
/**
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\CloudSubscription\Form;

use App\Error\Exception\CustomValidationException;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use Passbolt\CloudSubscription\Service\CloudSubscriptionSettings;

class CloudSubscriptionSettingsForm extends Form
{
    /**
     * Build the form schema
     *
     * @param Schema $schema schema
     * @return Schema
     */
    protected function _buildSchema(Schema $schema)
    {
        return $schema->addField('status', ['type' => 'string'])
            ->addField('expiryDate', ['type' => 'string']);
    }

    /**
     * Build the form validation rules
     *
     * @param Validator $validator validator
     * @return Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->allowEmptyDateTime('expiryDate')
            ->add('expiryDate', ['isValidTimeStamp' => [
                'rule' => [$this, 'isValidTimeStamp'],
                'message' => __('The date should be a valid unix timestamp.')
            ]]);

        $validator
            ->scalar('status')
            ->maxLength('status', 8)
            ->requirePresence('status')
            ->add('status', ['isValidStatusName' => [
                'rule' => [$this, 'isValidStatusName'],
                'message' => __('This status is not supported.')
            ]]);

        return $validator;
    }

    /**
     * @param int $timestamp timestamp
     * @return bool
     */
    public function isValidTimeStamp($timestamp)
    {
        return ((string)(int)$timestamp === $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * Custom validation rule to validate status name
     *
     * @param string $value name
     * @param array $context not in use
     * @return bool
     */
    public function isValidStatusName(string $value, array $context = null)
    {
        return in_array($value, CloudSubscriptionSettings::getSupportedStatusNames());
    }

    /**
     * Execute the form if it is valid.
     *
     * First validates the form, then calls the `_execute()` hook method.
     * This hook method can be implemented in subclasses to perform
     * the action of the form. This may be sending email, interacting
     * with a remote API, or anything else you may need.
     *
     * @param array $data Form data.
     * @return bool False on validation failure, otherwise returns the
     *   result of the `_execute()` method.
     */
    public function execute(array $data)
    {
        if (!$this->validate($data)) {
            throw new CustomValidationException(
                __('Something went wrong when validating the subscription settings.'),
                $this->getErrors()
            );
        }

        return $this->_execute($data);
    }

    /**
     * No data treatment, processing is handled in service
     *
     * @param array $data data
     * @return bool
     */
    protected function _execute(array $data)
    {
        return true;
    }
}
