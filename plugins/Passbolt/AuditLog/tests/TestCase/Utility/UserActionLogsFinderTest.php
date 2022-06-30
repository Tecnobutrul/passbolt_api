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

namespace Passbolt\AuditLog\Test\TestCase\Utility;

use App\Test\Factory\UserFactory;
use App\Utility\UserAccessControl;
use Cake\Utility\Hash;
use Passbolt\AuditLog\Utility\UserActionLogsFinder;
use Passbolt\Log\Test\Factory\ActionLogFactory;
use Passbolt\Log\Test\Factory\EntitiesHistoryFactory;
use Passbolt\Log\Test\Lib\LogIntegrationTestCase;

class UserActionLogsFinderTest extends LogIntegrationTestCase
{
    public function testUserActionLogsFinder()
    {
        // Create a set of random entity histories
        EntitiesHistoryFactory::make(10)->persist();
        // Create a set of random user related entity histories
        EntitiesHistoryFactory::make(10)->users()
            ->with('ActionLogs.Users')
            ->with('Users')
            ->persist();

        // Create the entity histories that the finder should retrieve
        $user = UserFactory::make()->persist();
        EntitiesHistoryFactory::make(5)->users()
            ->with('ActionLogs', ActionLogFactory::make()->with('Users')->inactive())
            ->with('Users', $user)
            ->persist();

        EntitiesHistoryFactory::make(5)
            ->users()
            ->create()
            ->with('ActionLogs', ActionLogFactory::make()->with('Users')->active())
            ->with('Users', $user)
            ->persist();

        $uac = $this->createMock(UserAccessControl::class);
        $actionLogs = (new UserActionLogsFinder())->find($uac, $user->id, [
            'sort' => 'ActionLogs.id',
            'direction' => 'DESC',
        ]);

        $sortedActionLogs = Hash::sort($actionLogs, '{n}.action_log_id', 'desc');
        $this->assertSame($actionLogs, $sortedActionLogs);

        foreach ($actionLogs as $actionLog) {
            $expectedData = ['user' => [
                'id' => $user->get('id'),
                'role_id' => $user->role_id,
                'username' => $user->username,
                'profile' => [
                    'first_name' => $user->profile->first_name,
                    'last_name' => $user->profile->last_name,
                ],
                'last_logged_in' => null,
            ]];
            $this->assertSame($expectedData, $actionLog['data']);
            $this->assertNotNull($actionLog['creator']);
        }
    }
}
