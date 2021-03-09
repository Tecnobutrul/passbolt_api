<?php
declare(strict_types=1);

/**
 * Passbolt Cloud
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\MultiTenantAdmin\Lib\Controller;

use App\Model\Entity\Role;
use App\Utility\UserAccessControl;
use App\Utility\UserAction;
use Cake\Controller\Controller;
use Cake\Routing\Router;
use Cake\Utility\Text;

/**
 * MultiTenant Admin Controller
 */
abstract class MultiTenantAdminController extends Controller
{
    /**
     * Initialization hook method.
     * Used to add common initialization code like loading components.
     *
     * @throws \Exception If a component class cannot be found.
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Passbolt/MultiTenantAdmin.AuthToken');

        $this->AuthToken->identify();

        // Init user action.
        $uac = new UserAccessControl(Role::ADMIN);
        UserAction::initFromRequest($uac, $this->request);
    }

    /**
     * Success renders set the variables used to render the json view
     * All passbolt response contains an header (metadata like status) an a body (data)
     *
     * @param string|null $message message in the header section
     * @param array $body data for the body section
     * @return void
     */
    protected function success(?string $message = null, $body = null)
    {
        $this->set([
            'header' => [
                'id' => Text::uuid(),
                'status' => 'success',
                'servertime' => time(),
                'message' => $message,
                'url' => Router::url(),
                'code' => 200,
            ],
            'body' => $body,
            '_serialize' => ['header', 'body'],
        ]);
    }

    /**
     * Error renders set the variables used to render the json view
     * All passbolt response contains an header (metadata like status) an a body (data)
     *
     * @param string|null $message message in the header section
     * @param mixed $body data for the body section
     * @return void
     */
    protected function error(?string $message = null, $body = null)
    {
        $this->set([
            'header' => [
                'id' => Text::uuid(),
                'status' => 'error',
                'servertime' => time(),
                'message' => $message ?? '',
                'url' => Router::url(),
                'code' => 200,
            ],
            'body' => $body ?? '',
            '_serialize' => ['header', 'body'],
        ]);
    }
}
