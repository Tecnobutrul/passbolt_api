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
namespace Passbolt\CloudSubscription\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

class SubscriptionStatusController extends AppController
{
    /**
     * Before filter
     *
     * @param Event $event An Event instance
     * @throws NotFoundException if registration is not set to public
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(Event $event)
    {
        $this->Auth->allow('getDisabled');
        $this->Auth->allow('getArchived');
        return parent::beforeFilter($event);
    }

    /**
     * Display information about subscription status
     *
     * @return void
     */
    public function getDisabled() {
        if (!$this->request->is('json')) {
            $this->viewBuilder()
                ->setLayout('default')
                ->setTemplate(ucfirst('disabled'));
        } else {
            $msg = __('This Passbolt Cloud instance is disabled. Please purchase a subscription.');
            throw new ForbiddenException($msg);
        }
        $this->success(__('The Passbolt Cloud subscription is not valid for this workspace. This workspace is disabled.'));
    }

    /**
     * Display information about subscription status
     *
     * @return void
     */
    public function getArchived() {
        if (!$this->request->is('json')) {
            $this->viewBuilder()
                ->setLayout('default')
                ->setTemplate(ucfirst('archived'));
        } else {
            $msg = __('This Passbolt cloud instance was deleted due to inactivity.');
            throw new ForbiddenException($msg);
        }
        $this->success(__('This passbolt workspace does not exist or have been deleted.'));
    }
}
