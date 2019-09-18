<?php
/**
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\CloudSubscription\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

class CloudSubscriptionParkingController extends AppController
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
        $this->Auth->allow('getNotFound');

        return parent::beforeFilter($event);
    }

    /**
     * Display information about subscription status
     *
     * @return void
     */
    public function getDisabled()
    {
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
    public function getNotFound()
    {
        if (!$this->request->is('json')) {
            $this->viewBuilder()
                ->setLayout('default')
                ->setTemplate(ucfirst('notfound'));
        } else {
            $msg = __('This Passbolt cloud instance was deleted due to inactivity.');
            throw new ForbiddenException($msg);
        }
        $this->success(__('This passbolt workspace does not exist or have been deleted.'));
    }
}
