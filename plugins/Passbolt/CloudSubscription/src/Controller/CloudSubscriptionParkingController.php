<?php
declare(strict_types=1);

/**
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\CloudSubscription\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;

class CloudSubscriptionParkingController extends AppController
{
    /**
     * Before filter
     *
     * @param \Cake\Event\Event $event An Event instance
     * @throws \Cake\Http\Exception\NotFoundException if registration is not set to public
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
        $msg = __('The Passbolt Cloud subscription is not valid for this workspace.') . ' ';
        $msg .= __('This workspace is disabled.');
        $this->success($msg);
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
