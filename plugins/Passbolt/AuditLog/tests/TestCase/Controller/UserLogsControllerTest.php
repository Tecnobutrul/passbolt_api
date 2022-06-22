<?php
declare(strict_types=1);

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
 * @since         3.7.0
 */

namespace Passbolt\AuditLog\Test\TestCase\Controller;

use App\Utility\UuidFactory;
use Passbolt\Log\Test\Lib\LogIntegrationTestCase;

/**
 * @uses \Passbolt\AuditLog\Controller\UserLogsController
 */
class UserLogsControllerTest extends LogIntegrationTestCase
{
    public function testUserLogsController_Not_Admin_Should_Not_Be_Authorized()
    {
        $this->logInAsUser();
        $this->getJson('/actionlog/user/foo.json');
        $this->assertResponseCode(403);
        $this->assertResponseContains('Only administrators can view user logs.');
    }

    public function testUserLogsController_User_Id_Not_UUID()
    {
        $this->logInAsAdmin();
        $this->getJson('/actionlog/user/foo.json');
        $this->assertResponseCode(400);
        $this->assertResponseContains('The user identifier should be a valid UUID.');
    }

    public function testUserLogsController_User_Does_Not_Exist()
    {
        $id = UuidFactory::uuid();
        $this->logInAsAdmin();
        $this->getJson('/actionlog/user/' . $id . '.json');
        $this->assertResponseOk();
    }
}
