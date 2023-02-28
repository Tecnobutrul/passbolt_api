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
 * @since         4.0.0
 */
namespace Passbolt\DirectorySync\Actions\Traits;

use App\Model\Entity\Group;
use App\Model\Entity\Role;
use App\Service\Groups\GroupsUpdateService;
use App\Utility\UserAccessControl;
use Passbolt\DirectorySync\Actions\Reports\ActionReport;
use Passbolt\DirectorySync\Model\Entity\DirectoryEntry;
use Passbolt\DirectorySync\Utility\Alias;
use Passbolt\DirectorySync\Utility\SyncError;

trait SyncUpdateTrait
{
    /**
     * Handle update group.
     *
     * @param array $data data
     * @param \Passbolt\DirectorySync\Model\Entity\DirectoryEntry|null $entry entry
     * @param \App\Model\Entity\Group $existingGroup Group
     * @return void
     */
    public function handleUpdateGroup(array $data, ?DirectoryEntry $entry, Group $existingGroup): void
    {
        $groupName = $this->getNameFromData($data);
        if ($groupName === 'undefined' || strtolower($groupName) === strtolower($existingGroup->name)) {
            return;
        }
        $this->updateGroup($existingGroup, $data);
    }

    /**
     * Update group
     *
     * @param \App\Model\Entity\Group $existingGroup Group
     * @param array $data data
     * @return void
     */
    public function updateGroup(Group $existingGroup, array $data): void
    {
        $uac = new UserAccessControl(Role::ADMIN, $this->defaultAdmin->get('id'));
        $groupsUpdateService = new GroupsUpdateService();
        $groupName = $this->getNameFromData($data);
        $changes = [
            'name' => $groupName,
        ];
        try {
            $entity = $groupsUpdateService->update($uac, $existingGroup->id, $changes);
            // Send report.
            $this->addReportItem(new ActionReport(
                __('The group {0} has been successfully renamed to {1}.', $existingGroup->name, $groupName),
                Alias::MODEL_GROUPS,
                Alias::ACTION_UPDATE,
                Alias::STATUS_SUCCESS,
                $entity
            ));
        } catch (\Exception $exception) {
            $error = new SyncError($existingGroup, $exception);
            $this->addReportItem(new ActionReport(
                __('The group {0} could not be renamed to {1}.', $existingGroup->name, $groupName),
                Alias::MODEL_GROUPS,
                Alias::ACTION_UPDATE,
                Alias::STATUS_ERROR,
                $error
            ));
        }
    }
}
