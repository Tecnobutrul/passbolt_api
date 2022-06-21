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
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         3.7.0
 */

namespace Passbolt\AuditLog\Utility;

use App\Utility\UserAccessControl;
use Cake\ORM\Query;

class UserActionLogsFinder extends BaseActionLogsFinder
{
    /**
     * @inheritDoc
     */
    public function find(UserAccessControl $user, string $entityId, ?array $options = []): array
    {
        // Build query.
        $query = $this->_getBaseQuery();
        // Filter the user
        $query->where(['EntitiesHistory.foreign_key' => $entityId]);
        // Join the user and profile
        $query->contain('EntitiesHistory.Users', function (Query $q) {
            return $q
                ->select(['Users.id', 'Users.role_id', 'Users.username'])
                ->contain('Profiles', function (Query $q) {
                    return $q->select([
                        'Profiles.first_name',
                        'Profiles.last_name',
                    ]);
                });
        });

        if (!empty($options)) {
            $query = $this->_paginate($query, $options);
        }
        $actionLogs = $query->all();
        $resultParser = new ActionLogResultsParser($actionLogs, ['users' => [$entityId]]);

        return $resultParser->parse();
    }
}
