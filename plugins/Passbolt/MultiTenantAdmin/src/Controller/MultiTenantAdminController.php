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
namespace Passbolt\MultiTenantAdmin\Controller;

use Cake\Controller\Controller;
use Cake\Routing\Router;
use Cake\Utility\Text;

/**
 * MultiTenant Controller
 */
class MultiTenantAdminController extends Controller
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Passbolt/MultiTenantAdmin.AuthToken');

        $this->AuthToken->identify();
    }

    /**
     * Success renders set the variables used to render the json view
     * All passbolt response contains an header (metadata like status) an a body (data)
     *
     * @param string $message message in the header section
     * @param array $body data for the body section
     * @return void
     */
    protected function success($message = null, $body = null)
    {
        $prefix = strtolower($this->request->getParam('prefix'));
        $action = $this->request->getParam('action');
        $this->set([
            'header' => [
                'id' => Text::uuid(),
                'status' => 'success',
                'servertime' => time(),
                'title' => 'app_' . $prefix . '_' . $action . '_success',
                'message' => $message,
                'url' => Router::url(),
                'code' => 200,
            ],
            'body' => $body,
            '_serialize' => ['header', 'body']
        ]);
    }

    /**
     * Error renders set the variables used to render the json view
     * All passbolt response contains an header (metadata like status) an a body (data)
     *
     * @param string $message message in the header section
     * @param array $body data for the body section
     * @return void
     */
    protected function error($message = null, $body = null)
    {
        $prefix = strtolower($this->request->getParam('prefix'));
        $action = $this->request->getParam('action');
        $this->set([
            'header' => [
                'id' => Text::uuid(),
                'status' => 'error',
                'servertime' => time(),
                'title' => 'app_' . $prefix . '_' . $action . '_error',
                'message' => $message,
                'url' => Router::url(),
                'code' => 200,
            ],
            'body' => $body,
            '_serialize' => ['header', 'body']
        ]);
    }
}
