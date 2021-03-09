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
 * @since         2.11.0
 */

namespace Passbolt\MultiTenantAdmin\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Passbolt\MultiTenantAdmin\Test\Lib\MultitenantAdminIntegrationTestCase;

class MultiTenantAdminRegisterFirstUserControllerTest extends MultitenantAdminIntegrationTestCase
{
    public $fixtures = [
        'plugin.Passbolt/MultiTenantAdmin.Base/Users', 'app.Base/Roles',
        'plugin.Passbolt/MultiTenantAdmin.Base/Profiles', 'plugin.Passbolt/MultiTenantAdmin.Base/Avatars',
        'plugin.Passbolt/MultiTenantAdmin.Base/AuthenticationTokens',
    ];

    public function tearDown()
    {
        TableRegistry::clear();
    }

    public function testMultiTenantAdminRegisterFirstUserControllerSuccess()
    {
        $postData = [
            'username' => 'org-admin@passbolt.com',
            'profile' => [
                'first_name' => 'Organization',
                'last_name' => 'Administrator',
            ],
        ];

        $this->setBasicAuth();
        $this->postJson('/multitenant/admin/register-first-user.json?api-version=v2', $postData);
        $this->assertSuccess();

        $Users = TableRegistry::getTableLocator()->get('Users');
        $AuthenticationTokens = TableRegistry::getTableLocator()->get('AuthenticationTokens');
        $user = $Users->find()->select()->first();
        $registrationToken = $AuthenticationTokens->find()->select()->first();
        $url = Router::url("/setup/install/{$user->id}/{$registrationToken->token}", true);
        $this->assertEquals($url, $this->_responseJsonBody);
    }

    public function testMultiTenantAdminRegisterFirstUserControllerError_InvalidUser()
    {
        $postData = [];
        $this->setBasicAuth();
        $this->postJson('/multitenant/admin/register-first-user.json?api-version=v2', $postData);
        $this->assertBadRequestError('Could not validate user data');
        $this->assertNotEmpty($this->_responseJsonBody->username->_required);
    }

    public function testMultiTenantAdminRegisterFirstUserControllerError_NotAuthorized()
    {
        $this->postJson('/multitenant/admin/register-first-user.json?api-version=v2');
        $this->assertForbiddenError('You are not authorized to access this location');
    }
}
