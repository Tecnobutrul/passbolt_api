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
 * @since         3.7.0
 */
namespace Passbolt\AuditLog\Test\TestCase\Controller\Users;

use App\Test\Factory\RoleFactory;
use App\Test\Factory\UserFactory;
use Passbolt\Log\Model\Entity\EntityHistory;
use Passbolt\Log\Test\Factory\ActionLogFactory;
use Passbolt\Log\Test\Lib\LogIntegrationTestCase;

class UsersDeleteControllerTest extends LogIntegrationTestCase
{
    public function testUsersDeleteSuccess()
    {
        RoleFactory::make()->guest()->persist();
        $this->logInAsAdmin();
        $user = UserFactory::make()->user()->persist();
        $this->deleteJson("/users/{$user->id}.json?api-version=v2");
        $this->assertSuccess();
        $retrievedUser = UserFactory::get($user->id);
        $this->assertTrue($retrievedUser->deleted);

        $this->assertActionLogsCount(1);
        $this->assertEntitiesHistoryCount(1, [
            'foreign_model' => 'Users',
            'foreign_key' => $user->id,
            'crud' => EntityHistory::CRUD_DELETE,
            'action_log_id' => ActionLogFactory::find()->firstOrFail()->get('id'),
        ]);
    }
}
