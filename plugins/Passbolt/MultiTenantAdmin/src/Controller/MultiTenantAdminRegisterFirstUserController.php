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

namespace Passbolt\MultiTenantAdmin\Controller;

use App\Model\Entity\Role;
use App\Utility\UserAccessControl;
use Cake\Http\Exception\InternalErrorException;
use Cake\Routing\Router;
use Passbolt\MultiTenantAdmin\Lib\Controller\MultiTenantAdminController;

class MultiTenantAdminRegisterFirstUserController extends MultiTenantAdminController
{
    /**
     * Initializes the Shell
     * acts as constructor for subclasses
     * allows configuration of tasks prior to shell execution
     *
     * @return void
     * @link https://book.cakephp.org/3.0/en/console-and-shells.html#Cake\Console\ConsoleOptionParser::initialize
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Users');
    }

    /**
     * Register the first user.
     *
     * @return void
     */
    public function registerFirstUser()
    {
        if (!$this->request->is('json')) {
            throw new BadRequestException(__('This is not a valid Ajax/Json request.'));
        }

        $countUsers = $this->Users->find()->select()->count();
        if ($countUsers) {
            throw new InternalErrorException(__('Cannot register a first user.'));
        }

        $accessControl = new UserAccessControl(Role::ADMIN);

        $data = $this->request->getData();
        $data['role_id'] = $this->Users->Roles->getIdByName(Role::ADMIN);
        $user = $this->Users->register($data, $accessControl);

        $registrationToken = $this->Users->AuthenticationTokens->getByUserId($user->id);
        $setupUrl = Router::url("/setup/install/{$user->id}/{$registrationToken->token}", true);

        $this->success(__('The operation was successful.'), $setupUrl);
    }
}
