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

namespace Passbolt\MultiTenantAdmin\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Passbolt\MultiTenantAdmin\Test\Lib\MultitenantAdminIntegrationTestCase;

class MultiTenantAdminMigrateControllerTest extends MultitenantAdminIntegrationTestCase
{
    public $fixtures = [
        'plugin.Passbolt/MultiTenantAdmin.Base/Users', 'app.Base/Roles',
        'plugin.Passbolt/MultiTenantAdmin.Base/Profiles', 'plugin.Passbolt/MultiTenantAdmin.Base/Avatars',
        'plugin.Passbolt/MultiTenantAdmin.Base/AuthenticationTokens'
    ];

    public function tearDown()
    {
        TableRegistry::clear();
    }

    public function testError_NotAuthorized()
    {
        $this->getJson("/multitenant/admin/migrate.json?api-version=v2");
        $this->assertForbiddenError('You are not authorized to access this location');
    }
}
