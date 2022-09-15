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
 * @since         2.13.0
 */

namespace Passbolt\Folders\Service\FoldersRelations;

use App\Utility\UserAccessControl;
use Cake\ORM\TableRegistry;
use Passbolt\Folders\Model\Entity\FoldersRelation;

class FoldersRelationsRepairStronglyConnectedComponentsService
{
    /**
     * @var \Passbolt\Folders\Model\Table\FoldersRelationsTable
     */
    private $foldersRelationsTable;

    /**
     * Instantiate the service.
     */
    public function __construct()
    {
        /** @phpstan-ignore-next-line */
        $this->foldersRelationsTable = TableRegistry::getTableLocator()->get('Passbolt/Folders.FoldersRelations');
    }

    /**
     * Repair a set of strongly connected components.
     *
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @param string $userId The user tree that is at the origin at the conflict. Most of the time it's the modified
     *                        tree.
     * @param array $foldersRelations The relations forming a strongly connected components set
     * @return void
     */
    public function repair(UserAccessControl $uac, string $userId, array $foldersRelations): void
    {
        $folderRelationToBreak = $this->identifyFolderRelationToBreak($uac, $userId, $foldersRelations);
        $this->foldersRelationsTable->moveItemFrom(
            $folderRelationToBreak['foreign_id'],
            [$folderRelationToBreak['folder_parent_id']],
            FoldersRelation::ROOT
        );
    }

    /**
     * Identify the relation to break in order to solve an SCC.
     *
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @param string $userId The user tree that is at the origin at the conflict. Most of the time it's the modified
     * tree.
     * @param array $foldersRelations The list of folders relations responsible of the conflict.
     * @return array
     * [
     *   string $foreign_id The item id
     *   string $folder_parent_id The item folder parent
     *   bool $inOperatorTree Does the operator see the relation
     *   bool $inUserTree Does the user see the relation
     *   int $usedCount How many user see the relation
     *   string $created The oldest time this relation has been created
     * ]
     */
    private function identifyFolderRelationToBreak(
        UserAccessControl $uac,
        string $userId,
        array $foldersRelations
    ): array {
        // Retrieve the folders relations info that will help to prioritize the relation to break.
        $foldersRelationsInfo = [];
        foreach ($foldersRelations as $i => $folderRelation) {
            $foldersRelationsInfo[] = $this->getFolderRelationInfo(
                $uac,
                $userId,
                $folderRelation['foreign_id'],
                $folderRelation['folder_parent_id']
            );
        }

        // Sort the folders relations info and identify in the top of the list the folder relation to break.
        $foldersRelationsInfo = $this->sortFolderRelationsByPriority($uac, $foldersRelationsInfo);

        return [
            'foreign_id' => $foldersRelationsInfo[0]['foreignId'],
            'folder_parent_id' => $foldersRelationsInfo[0]['folderParentId'],
        ];
    }

    /**
     * Retrieve a folder relation information that will help to prioritize the relation to break.
     *
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @param string $userId The user tree that is at the origin at the conflict. Most of the time it's the modified
     * @param string $foreignId The relation item id
     * @param string $folderParentId The relation parent item id
     * @return array
     */
    private function getFolderRelationInfo(
        UserAccessControl $uac,
        string $userId,
        string $foreignId,
        string $folderParentId
    ): array {
        $inOperatorTree = $this->foldersRelationsTable->isItemOrganizedInUserTree(
            $uac->getId(),
            $foreignId,
            $folderParentId
        );
        $inUserTree = $this->foldersRelationsTable->isItemOrganizedInUserTree($userId, $foreignId, $folderParentId);
        $usedCount = $this->foldersRelationsTable->countRelationUsage($foreignId, $folderParentId);
        $created = $this->foldersRelationsTable->getRelationOldestCreatedDate($foreignId, $folderParentId);

        return [
            'foreignId' => $foreignId,
            'folderParentId' => $folderParentId,
            'inOperatorTree' => $inOperatorTree,
            'inUserTree' => $inUserTree,
            'usedCount' => $usedCount,
            'created' => $created,
        ];
    }

    /**
     * Sort the list of conflicted folders relations information list by priority. Move to the top the relation that
     * is most likely to be broken. The priority of keeping a relation is calculated as following:
     * 1. The operator relations;
     * 2. The most used relations;
     * 3. The relations that are in the tree of the user impacted by the action;
     * 4. The oldest relations.
     *
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @param array $foldersRelations The relations forming a strongly connected components set
     * @return array
     */
    private function sortFolderRelationsByPriority(UserAccessControl $uac, array $foldersRelations): array
    {
        usort($foldersRelations, function ($folderRelationA, $folderRelationB) {
            // Operator relations should be broken with the lowest priority.
            if ($folderRelationA['inOperatorTree']) {
                return 1;
            } elseif ($folderRelationB['inOperatorTree']) {
                return -1;
            }
            // Otherwise most used relations should be broken with the lowest priority.
            if ($folderRelationA['usedCount'] > $folderRelationB['usedCount']) {
                return 1;
            } elseif ($folderRelationA['usedCount'] < $folderRelationB['usedCount']) {
                return -1;
            }
            // Otherwise relations in the user tree should be broken with the lowest priority.
            if ($folderRelationA['inUserTree']) {
                return 1;
            } elseif ($folderRelationB['inUserTree']) {
                return -1;
            }
            // Otherwise oldest relations should be broken with the lowest priority.
            if ($folderRelationA['created'] < $folderRelationB['created']) {
                return 1;
            } elseif ($folderRelationA['created'] > $folderRelationB['created']) {
                return -1;
            }

            return -1;
        });

        return $foldersRelations;
    }
}
