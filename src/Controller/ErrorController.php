<?php
declare(strict_types=1);

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
 * @since         2.0.0
 */
namespace App\Controller;

use App\Error\Exception\ExceptionWithErrorsDetailInterface;
use App\Utility\UserAction;
use App\Utility\UuidFactory;
use Cake\Core\Exception\Exception;
use Cake\Event\Event;
use Cake\Http\Exception\InternalErrorException;
use Cake\Routing\Router;

/**
 * Error Handling Controller
 *
 * Controller used by ExceptionRenderer to render error responses.
 */
class ErrorController extends AppController
{
    /**
     * Initialization hook method.
     *
     * @throws \Exception If a component class cannot be found.
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('RequestHandler', [
            'enableBeforeRedirect' => false,
        ]);
    }

    /**
     * beforeRender callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return \Cake\Network\Response|null|void
     */
    public function beforeRender(Event $event)
    {
        if ($this->request->is('json')) {
            // If the body is a that exposes the getErrors functionality
            // for example ValidationRulesException
            $error = $this->viewVars['error'];

            if ($error instanceof ExceptionWithErrorsDetailInterface) {
                $body = $error->getErrors();
            }

            try {
                $userActionId = UserAction::getInstance()->getUserActionId();
            } catch (Exception $e) {
                $userActionId = UuidFactory::uuid();
            }
            try {
                $actionId = UserAction::getInstance()->getActionId();
            } catch (Exception $e) {
                $actionId = 'undefined';
            }
            $this->set([
                'header' => [
                    'id' => $userActionId,
                    'status' => 'error',
                    'servertime' => time(),
                    'action' => $actionId,
                    'message' => $this->viewVars['message'],
                    'url' => Router::url(),
                    'code' => $this->viewVars['code'],
                ],
                'body' => $body ?? '',
                '_serialize' => ['header', 'body'],
            ]);

            // render a legacy JSON view by default
            $apiVersion = $this->request->getQuery('api-version');
            if ($apiVersion === 'v1') {
                throw new InternalErrorException(__('API v1 support is deprecated in this version.'));
            }
        }
        $this->viewBuilder()->setTemplatePath('Error');
    }
}
