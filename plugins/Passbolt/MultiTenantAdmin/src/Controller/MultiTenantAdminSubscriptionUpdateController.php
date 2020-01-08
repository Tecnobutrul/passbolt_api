<?php
/**
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\MultiTenantAdmin\Controller;

use Cake\Http\Exception\BadRequestException;
use Passbolt\CloudSubscription\Service\CloudSubscriptionSettings;
use Passbolt\MultiTenantAdmin\Lib\Controller\MultiTenantAdminController;

class MultiTenantAdminSubscriptionUpdateController extends MultiTenantAdminController
{
    /**
     * Update the cloud subscription status or create one
     * Requires system privileges
     *
     * @return void
     */
    public function updateOrCreate()
    {
        if (!$this->request->is('json')) {
            throw new BadRequestException(__('This is not a valid Ajax/Json request.'));
        }

        $data = $this->getRequest()->getData();
        if (!isset($data)) {
            throw new BadRequestException(__('Subscription data is required.'));
        }

        CloudSubscriptionSettings::updateOrCreate($data);
        $this->success(__('The operation was successful.'));
    }
}
