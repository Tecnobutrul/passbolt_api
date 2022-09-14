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

use App\Model\Table\PermissionsTable;
use App\Utility\UserAccessControl;
use Cake\Database\Expression\TupleComparison;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Passbolt\Folders\Model\Entity\FoldersRelation;

class FoldersRelationsAddItemsToUserTreeService
{
    /**
     * @var \Passbolt\Folders\Model\Table\FoldersRelationsTable
     */
    private $foldersRelationsTable;

    /**
     * @var \App\Model\Table\PermissionsTable
     */
    private $permissionsTables;

    /**
     * @var \App\Model\Table\UsersTable
     */
    private $usersTable;

    /**
     * @var \Passbolt\Folders\Service\FoldersRelations\FoldersRelationsSortService
     */
    private $foldersRelationsSortService;

    /**
     * @var \Passbolt\Folders\Service\FoldersRelations\FoldersRelationsCreateService
     */
    private $foldersRelationsCreateService;

    /**
     * @var \Passbolt\Folders\Service\FoldersRelations\FoldersRelationsDetectStronglyConnectedComponentsService
     */
    private $folderRelationsDetectSCCsService;

    /**
     * @var \Passbolt\Folders\Service\FoldersRelations\FoldersRelationsRepairStronglyConnectedComponentsService
     */
    private $foldersRelationsRepairSCCsService;

    /**
     * Instantiate the service.
     */
    public function __construct()
    {
        /** @phpstan-ignore-next-line */
        $this->foldersRelationsTable = TableRegistry::getTableLocator()->get('Passbolt/Folders.FoldersRelations');
        /** @phpstan-ignore-next-line */
        $this->permissionsTables = TableRegistry::getTableLocator()->get('Permissions');
        /** @phpstan-ignore-next-line */
        $this->usersTable = TableRegistry::getTableLocator()->get('Users');
        $this->foldersRelationsSortService = new FoldersRelationsSortService();
        $this->foldersRelationsCreateService = new FoldersRelationsCreateService();
        $this->folderRelationsDetectSCCsService = new FoldersRelationsDetectStronglyConnectedComponentsService();
        $this->foldersRelationsRepairSCCsService = new FoldersRelationsRepairStronglyConnectedComponentsService();
    }

    /**
     * Add items to a user tree.
     *
     * This function doesn't check if the user has access to the items to add.
     * This function doesn't check if the items are already present in the user tree.
     *
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @param string $userId The target user id the items are added for
     * @param array $items The list of items to add to the tree
     * For performance reason on large operation such as user to group, this operation is already guaranteed by the caller.
     * @return void
     * @throws \Exception If an unexpected error occurred
     * @todo Format of the items parameter is error prone. Review the format and validation.
     */
    public function addItemsToUserTree(UserAccessControl $uac, string $userId, array $items): void
    {
        $foldersRelationsChanges = $this->getFoldersRelationsChanges($uac, $userId, $items);
        $this->insertItemsInUserRootTree($userId, $items);
        if (!empty($foldersRelationsChanges)) {
            $this->applyFoldersRelationsChanges($userId, $foldersRelationsChanges);
            $this->detectAndRepairSCCs($uac, $userId, $foldersRelationsChanges);
            $this->detectAndRepairSCCsInUserTree($userId);
        }
    }

    /**
     * Get the list of folders relations changes to apply to the user tree in order to support the insertion of a
     * list of given items (resources and folders).
     *
     * The list of folders relations changes will be sorted as following (on top the changes to apply in priority):
     * 1. The folder relation presence in the operator tree. Priority to the operator view.
     * 2. The folder relation usage. Priority to the more used.
     * 3. The folder relation age. Priority to the oldest folder relation.
     *
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @param string $userId The target user id the items are added for
     * @param array $items The list of items to add to the tree
     * @return array
     */
    private function getFoldersRelationsChanges(UserAccessControl $uac, string $userId, array $items): array
    {
        $parentFoldersRelationsChanges = $this->getParentFoldersRelationsChanges($userId, $items);
        $childrenFoldersRelationsChanges = $this->getChildrenFoldersRelationsChanges(
            $userId,
            $items,
            $parentFoldersRelationsChanges
        );
        $foldersRelationChanges = array_merge($parentFoldersRelationsChanges, $childrenFoldersRelationsChanges);
        $this->foldersRelationsSortService->sort($foldersRelationChanges, $uac);

        return $foldersRelationChanges;
    }

    /**
     * Returns a list of folders relations which could represent a potential parent relationship for the list of
     * items added to the user tree.
     *
     * @param string $userId The target user id the items are added for
     * @param array $items The items to look for potential parents
     * @return array<string>
     */
    private function getParentFoldersRelationsChanges(string $userId, array $items): array
    {
        // R = The folders relations which could represent a potential parent relationship for the list of items added
        //     to the user tree.
        //
        // Details :
        // USERS_FOLDERS = The folders the target user can see
        // POTENTIAL_PARENTS = The parents of the newly added items in all the users trees
        // R = ITEMS_POTENTIAL_PARENTS ⋂ USERS_FOLDERS

        // USERS_FOLDERS
        $userFolders = $this->permissionsTables->findAllByAro(
            PermissionsTable::FOLDER_ACO,
            $userId,
            ['checkGroupsUsers' => true]
        )
            ->select('aco_foreign_key');

        // POTENTIAL_PARENTS
        $foreignIds = Hash::extract($items, '{n}.foreign_id');
        $query = $this->foldersRelationsTable->find()
            ->where(['foreign_id IN' => $foreignIds]);

        // R = POTENTIAL_PARENTS ⋂ USERS_FOLDERS
        $query->where(['folder_parent_id IN' => $userFolders]);

        return $query->select(['foreign_id', 'folder_parent_id'])
            ->group(['foreign_id', 'folder_parent_id'])
            ->all()
            ->toArray();
    }

    /**
     * Returns a list of folders relations which could represent a potential child relationship for the list of folders
     * added to the user tree.
     *
     * @param string $userId The target user id the items are added for
     * @param array $items The items to look for potential parents
     * @param array $excludeFoldersRelations The folders relations to exclude
     * @return array<string>
     */
    private function getChildrenFoldersRelationsChanges(
        string $userId,
        array $items,
        array $excludeFoldersRelations
    ): array {
        // R = The folders relations which could represent a potential child relationship for the list of folders added
        //     to the user tree.
        //
        // Details :
        // USERS_ITEMS = The items (folders or resources) the target user can see
        // POTENTIAL_CHILDREN = The children of the newly added items in all the users trees
        // R = POTENTIAL_CHILDREN ⋂ USERS_ITEMS

        // CHILDREN
        // @todo Fix this along the format of the list of items.
        $foreignIds = Hash::extract($items, '{n}[foreign_model=Folder].foreign_id');
        if (empty($foreignIds)) {
            return [];
        }

        // POTENTIAL_CHILDREN
        $query = $this->foldersRelationsTable->find();
        $query->where(['folder_parent_id IN' => $foreignIds]);

        // R = POTENTIAL_CHILDREN ⋂ USERS_ITEMS
        $userItems = $this->foldersRelationsTable->findByUserId($userId);
        $query->where(['foreign_id IN' => $userItems->select('foreign_id')]);

        if (!empty($excludeFoldersRelations)) {
            $query->where($this->buildFoldersRelationsTupleComparisonExpression($excludeFoldersRelations, false));
        }

        return $query->select(['foreign_id', 'folder_parent_id'])
            ->group(['foreign_id', 'folder_parent_id'])
            ->all()
            ->toArray();
    }

    /**
     * Build a folders relations IN or NOT IN tuple comparison used in query where clause.
     * Output SQL like:
     * WHERE (foreign_id, folder_parent_id) IN ((FOLDER_RELATION_1_FOREIGN_ID, FOLDER_RELATION_1_FOLDER_PARENT_ID), ...)
     *
     * @param array<\Passbolt\Folders\Model\Entity\FoldersRelation> $foldersRelations The folders relations to build a tuple comparison expression for
     * @param bool $isInOperator (Optional) By default true and the expression with use the IN operator. If false the
     * expression will use the NOT IN operator.
     * @return \Cake\Database\Expression\TupleComparison
     */
    private function buildFoldersRelationsTupleComparisonExpression(
        array $foldersRelations,
        bool $isInOperator = true
    ): TupleComparison {
        $operator = $isInOperator ? 'IN' : 'NOT IN';
        $excludeFoldersRelationsArray = array_map(function (FoldersRelation $excludeFolderRelation) {
            return $excludeFolderRelation->extract(['foreign_id', 'folder_parent_id']);
        }, $foldersRelations);

        return new TupleComparison(['foreign_id', 'folder_parent_id'], $excludeFoldersRelationsArray, [], $operator);
    }

    /**
     * Insert the items at the root of the user's tree.
     *
     * @param string $userId The target user id the items are added for
     * @param array $items The list of items to add to the tree
     * @return void
     * @throws \Exception If a folder relation cannot be created
     */
    private function insertItemsInUserRootTree(string $userId, array $items)
    {
        foreach ($items as $item) {
            $this->foldersRelationsCreateService
                ->create(
                    $item['foreign_model'],
                    $item['foreign_id'],
                    $userId,
                    FoldersRelation::ROOT,
                    false
                );
        }
    }

    /**
     * Apply the folders relations changes to the user tree.
     *
     * @param string $userId The target user id the items are added for
     * @param array<\Passbolt\Folders\Model\Entity\FoldersRelation> $foldersRelationsChanges The folders relations changes
     * @return void
     */
    private function applyFoldersRelationsChanges(string $userId, array $foldersRelationsChanges): void
    {
        $changesToApply = [];
        $changesToCancel = [];

        /**
         * Sort out the changes to apply and the changes to cancel. Only one folder relation change for a given item
         * defined by its foreign_id can be played, other changes for the same item will be cancelled.
         * Regroup the folders relations changes to apply by folder parent. This will improve the performance by
         * reducing the number of SQL queries made.
         */
        $appliedChangesHash = [];
        foreach ($foldersRelationsChanges as $folderRelationChange) {
            if (isset($appliedChangesHash[$folderRelationChange->foreign_id])) {
                $changesToCancel[] = $folderRelationChange;
                continue;
            }
            $appliedChangesHash[$folderRelationChange->foreign_id] = true;
            $changesToApply[$folderRelationChange->folder_parent_id][] = $folderRelationChange->foreign_id;
        }

        // Move to the root the items for which the folders relations changes got cancelled.
        if (!empty($changesToCancel)) {
            $this->foldersRelationsTable->updateAll([
                'folder_parent_id' => FoldersRelation::ROOT,
            ], $this->buildFoldersRelationsTupleComparisonExpression($changesToCancel));
        }

        // Apply the changes to the user tree.
        foreach ($changesToApply as $folderParentId => $foreignIds) {
            $this->foldersRelationsTable->updateAll(
                ['folder_parent_id' => $folderParentId],
                ['user_id' => $userId, 'foreign_id IN' => $foreignIds]
            );
        }
    }

    /**
     * Detect and repair any strongly
     *
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the action
     * @param string $userId The target user id the items are added for
     * @param array<\Passbolt\Folders\Model\Entity\FoldersRelation> $foldersRelationsChanges The folders relations changes
     * @return void
     * @throw InternalErrorException If a SCC is found but cannot be repaired.
     */
    private function detectAndRepairSCCs(UserAccessControl $uac, string $userId, array $foldersRelationsChanges): void
    {
        /*
         * Retrieving the users having a tree related or impacted by the folders relations changes is slower than
         * trying to find SCCs by comparing all users trees.
         * $this->foldersRelationsTable->find()
         *   ->where($this->buildFoldersRelationsTupleComparisonExpression($foldersRelationsChanges))
         */
        $usersIds = $this->usersTable->findActive()->select('id')->all()->extract('id')->toArray();
        $foldersRelationsScc = $this->folderRelationsDetectSCCsService->bulkDetectForUsers($usersIds);

        if (!empty($foldersRelationsScc)) {
            $brokenFolderRelation = $this->foldersRelationsRepairSCCsService->repair(
                $uac,
                $userId,
                $foldersRelationsScc
            );

            if (!$brokenFolderRelation) {
                $msg = "Strongly connected components found, but cannot be repaired."; // phpcs:ignore
                throw new InternalErrorException($msg);
            }
            $this->detectAndRepairSCCs($uac, $userId, $foldersRelationsChanges);
        }
    }

    /**
     * Look for a cycle in the target user tree the items are added for and repair it.
     *
     * @param string $userId The target user id the items are added for
     * @return void
     * @throw InternalErrorException If a SCC is found but is not relative to a user personal folder.
     */
    private function detectAndRepairSCCsInUserTree(string $userId): void
    {
        $foldersRelationsScc = $this->folderRelationsDetectSCCsService->detectInUserTree($userId);

        if (!empty($foldersRelationsScc)) {
            $brokenFolderRelation = $this->foldersRelationsRepairSCCsService->repairPersonal($foldersRelationsScc);

            // If a cycle is found, but it does not include a personal folder, then we have an integrity issue with the
            // graph. The cleanup task should identify and solve this issue.
            if (!$brokenFolderRelation) {
                $msg = "Strongly connected components found in the tree of ($userId), but it is not related to a personal folder."; // phpcs:ignore
                throw new InternalErrorException($msg);
            }
            $this->detectAndRepairSCCsInUserTree($userId);
        }
    }
}
