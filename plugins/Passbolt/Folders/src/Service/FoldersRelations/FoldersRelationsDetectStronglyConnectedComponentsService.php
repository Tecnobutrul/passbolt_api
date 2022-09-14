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

use Cake\ORM\TableRegistry;
use Passbolt\Folders\Model\Entity\FoldersRelation;
use Passbolt\Folders\Utility\Tarjan;

class FoldersRelationsDetectStronglyConnectedComponentsService
{
    /**
     * @var \Passbolt\Folders\Model\Table\FoldersRelationsTable
     */
    private $foldersRelationsTable;

    /**
     * @var \App\Model\Table\UsersTable
     */
    private $usersTable;

    /**
     * Instantiate the service.
     */
    public function __construct()
    {
        /** @phpstan-ignore-next-line */
        $this->foldersRelationsTable = TableRegistry::getTableLocator()->get('Passbolt/Folders.FoldersRelations');
        /** @phpstan-ignore-next-line */
        $this->usersTable = TableRegistry::getTableLocator()->get('Users');
    }

    /**
     * Bulk detect strongly connected components for a list of given users.
     * Compare the tree of the given users with the trees of all the non deleted users.
     *
     * The function stops and returns the first SCC found.
     *
     * @param array $usersIds The list of users ids to check for
     * @return array<\Passbolt\Folders\Model\Entity\FoldersRelation> The folders relations involved in the strongly connected components set.
     */
    public function bulkDetectForUsers(array $usersIds)
    {
        $result = [];
        $usersIdsToCompareWith = $this->usersTable->findActive()
            ->all()
            ->extract('id')
            ->toArray();
        $usersFoldersRelations = $this->getUsersFoldersRelationsGroupedByUser($usersIdsToCompareWith);

        foreach ($usersIds as $firstUserId) {
            foreach ($usersIdsToCompareWith as $secondUserId) {
                $foldersRelations = array_merge(
                    $usersFoldersRelations[$firstUserId],
                    $usersFoldersRelations[$secondUserId]
                );
                $scc = $this->detectInFoldersRelations($foldersRelations);
                if (!empty($scc)) {
                    return $scc;
                }
            }
            // Avoid comparing users that have already been compared. As the user has already been compared with all non
            // deleted users, then it has already been compared with all the users given in parameter, remove it from
            // the list of users to compare with
            $firstUserIndex = array_search($firstUserId, $usersIdsToCompareWith);
            if ($firstUserIndex !== false) {
                unset($usersIdsToCompareWith[$firstUserIndex]);
            }
        }

        return $result;
    }

    /**
     * Retrieve folders relations for a given list of users and group them by users.
     *
     * @param array $usersIds The users to retrieve the folders relations for
     * @param bool $includePersonal Include personal folders. Default false
     * @return array<array<\Passbolt\Folders\Model\Entity\FoldersRelation>> Return an array of folders relations grouped by users ids
     * [
     * UUID => [<FoldersRelation>, ...],
     * UUID => [<FoldersRelation>, ...],
     * ...
     * ]
     */
    private function getUsersFoldersRelationsGroupedByUser(array $usersIds, ?bool $includePersonal = false): array
    {
        $result = array_fill_keys($usersIds, []);

        $query = $this->foldersRelationsTable->find();
        $query = $this->foldersRelationsTable->filterByForeignModel($query, FoldersRelation::FOREIGN_MODEL_FOLDER);
        $query = $this->foldersRelationsTable->filterByUsersIds($query, $usersIds);
        if (!$includePersonal) {
            $query = $this->foldersRelationsTable->filterQueryByIsNotPersonalFolder($query);
        }
        $foldersRelations = $query->select(['foreign_id', 'folder_parent_id', 'user_id'])
            ->all()
            ->toArray();

        foreach ($foldersRelations as $folderRelation) {
            $result[$folderRelation->user_id][] = $folderRelation;
        }

        return $result;
    }

    /**
     * Return the first detected strongly components set represented as an array of folders relations.
     *
     * @param array<\Passbolt\Folders\Model\Entity\FoldersRelation> $foldersRelations An array folders relations to test
     * @return array<\Passbolt\Folders\Model\Entity\FoldersRelation>
     * @throws \Exception If it cannot format the result.
     */
    private function detectInFoldersRelations(array $foldersRelations): array
    {
        $result = [];

        [$graph, $graphForeignIdsMap] = $this->formatFoldersRelationInAdjacencyGraph($foldersRelations);
        $stronglyConnectedComponentsSets = Tarjan::detect($graph);
        if (!empty($stronglyConnectedComponentsSets)) {
            $nodes = explode('|', $stronglyConnectedComponentsSets[0]);
            $result = $this->formatDetectInGraphResultInFoldersRelations(
                $nodes,
                $graphForeignIdsMap,
                $foldersRelations
            );
        }

        return $result;
    }

    /**
     * Format the algorithm result list into a folders relations list.
     *
     * @param array $nodes A list of integers representing the strongly connected components set
     * [0, 2, 3, 5, 1]
     * @param array $graphForeignIdsMap The nodes map. The map key is relative to a node when the value is relative to
     * a folder id.
     * @param array<\Passbolt\Folders\Model\Entity\FoldersRelation> $foldersRelations The folders relations to search
     * a SCC in.
     * @return array<\Passbolt\Folders\Model\Entity\FoldersRelation>
     * @throws \Exception If it cannot format the result because a folder relation relative to a node cannot be found.
     */
    private function formatDetectInGraphResultInFoldersRelations(
        array $nodes,
        array $graphForeignIdsMap,
        array $foldersRelations
    ): array {
        $result = [];

        /** @var int $i */
        foreach ($nodes as $i => $node) {
            $foreignId = $graphForeignIdsMap[$node];
            // If first node, then its parent is the last element of the list, otherwise the previous one.
            $folderParentIdIndex = $i === 0 ? count($nodes) - 1 : $i - 1;
            $folderParentId = $graphForeignIdsMap[$nodes[$folderParentIdIndex]];
            // Retrieve the relative folder relation.
            $result[] = $this->searchFolderRelationInArray($foldersRelations, $foreignId, $folderParentId);
        }

        return $result;
    }

    /**
     * Get an adjacency graph relative to the aggregated trees of the users given in parameter.
     *
     * @param array<\Passbolt\Folders\Model\Entity\FoldersRelation> $foldersRelations The folders relations to format.
     * @return array
     * [
     *   array $graph The tarjan adjacency graph
     *   array $graphForeignIdsMap The mapping between the tarjan graph nodes id and the folders id
     * ]
     *
     * graph. The key represents a node id, and its value a list of children nodes ids. Tarjan algorithm requires these
     * ids to be expressed as integer.
     *
     * [
     *   0 => [1,2]
     *   1 => [3]
     *   2 => [4]
     * ]
     *
     * graphForeignIdsMap. The key represents a tarjan node id (integer). The value represents the mapped folder id (uuid).
     * the array
     * [
     *   0 => e97b14ba-8957-57c9-a357-f78a6e1e1a46
     *   1 => 904bcd9f-ff51-5cfd-9de8-d2c876ade498
     * ]
     */
    private function formatFoldersRelationInAdjacencyGraph(array $foldersRelations): array
    {
        $graphForeignIdsMap = [];
        $graph = [];
        $graphCount = 0;

        // Build the adjacency graph.
        foreach ($foldersRelations as $folderRelation) {
            if (!isset($graphForeignIdsMap[$folderRelation->foreign_id])) {
                $graphForeignIdsMap[$folderRelation->foreign_id] = $graphCount++;
                $graph[$graphForeignIdsMap[$folderRelation->foreign_id]] = [];
            }

            if (!is_null($folderRelation->folder_parent_id)) {
                if (!isset($graphForeignIdsMap[$folderRelation->folder_parent_id])) {
                    $graphForeignIdsMap[$folderRelation->folder_parent_id] = $graphCount++;
                    $graph[$graphForeignIdsMap[$folderRelation->folder_parent_id]] = [];
                }
                $graph[$graphForeignIdsMap[$folderRelation->folder_parent_id]][] =
                    &$graphForeignIdsMap[$folderRelation->foreign_id];
            }
        }

        $graphForeignIdsMap = array_flip($graphForeignIdsMap);

        return [$graph, $graphForeignIdsMap];
    }

    /**
     * Search a folder relation by its foreign id and folder parent id in an array of folders relations.
     *
     * @param array $foldersRelations The haystack
     * @param string $foreignId The needle foreign id
     * @param string|null $folderParentId The needle folder parent id
     * @return \Passbolt\Folders\Model\Entity\FoldersRelation
     * @throws \Exception If a folder relation cannot be found.
     */
    private function searchFolderRelationInArray(
        array $foldersRelations,
        string $foreignId,
        ?string $folderParentId = null
    ): FoldersRelation {
        foreach ($foldersRelations as $folderRelation) {
            if ($folderRelation->foreign_id === $foreignId && $folderRelation->folder_parent_id === $folderParentId) {
                return $folderRelation;
            }
        }

        throw new \Exception('Unable to find a folder relation.');
    }

    /**
     * Detect the first strongly connected components set in a given user tree.
     * The detection also includes personal folders.
     * The script stops and returns the first SCC found.
     *
     * @param string $userId The target user
     * @return array<\Passbolt\Folders\Model\Entity\FoldersRelation> The list of folders relations involved in the strongly connected components set
     */
    public function detectInUserTree(string $userId): array
    {
        $query = $this->foldersRelationsTable->findByUserId($userId);
        $query = $this->foldersRelationsTable->filterByForeignModel($query, FoldersRelation::FOREIGN_MODEL_FOLDER);
        $foldersRelations = $query->select(['foreign_id', 'folder_parent_id'])
            ->all()->toArray();

        return $this->detectInFoldersRelations($foldersRelations);
    }
}
