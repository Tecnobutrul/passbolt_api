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

namespace Passbolt\AuditLog\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Validation\Validation;
use Passbolt\AuditLog\Utility\UserActionLogsFinder;

class UserLogsController extends BaseLogsController
{
    /**
     * View action logs for a given user.
     *
     * @param string|null $userId user id
     * @return void
     * @throws \Cake\Http\Exception\BadRequestException if the user id has the wrong format
     * @throws \Cake\Http\Exception\ForbiddenException if the UAC is not admin
     */
    public function view(?string $userId = null)
    {
        if (!$this->User->isAdmin()) {
            throw new ForbiddenException(__('Only administrators can view user logs.'));
        }

        // Check request sanity
        if (!Validation::uuid($userId)) {
            throw new BadRequestException(__('The user identifier should be a valid UUID.'));
        }

        $this->viewByEntity(new UserActionLogsFinder(), $userId);
    }
}
