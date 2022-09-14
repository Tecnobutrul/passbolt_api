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
 * @since         3.7.2
 */

namespace Passbolt\Folders\Service\FoldersRelations;

use App\Utility\UserAccessControl;
use Cake\Database\Expression\TupleComparison;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Passbolt\Folders\Model\Entity\FoldersRelation;

class FoldersRelationsSortService
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
     * Sort a list of folders relations as following (On top the items with the greatest priority):
     * 1. The folder relation presence in the operator tree. Priority to the operator view.
     * 2. The folder relation usage. Priority to the more used.
     * 3. (Optional) The folder relation presence in the target user tree. Priority to the target user view.
     * 4. The folder relation age. Priority to the oldest folder relation.
     *
     * @param array<FoldersRelation> &$folderRelations The array of folders relations to sort.
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the action.
     * @param string|null $userId The target user id
     */
    public function sort(array &$folderRelations, UserAccessControl $uac, ?string $userId = null)
    {
        $foldersRelationsChangesDetails = $this->getFolderRelationsDetails($folderRelations, $uac, $userId);

        usort($folderRelations, function (FoldersRelation $folderRelationA, FoldersRelation $folderRelationB) use ($foldersRelationsChangesDetails, $userId) {
            $isFolderRelationAInOperatorTree = $this->isFolderRelationInOperatorTree($folderRelationA, $foldersRelationsChangesDetails);
            $isFolderRelationBInOperatorTree = $this->isFolderRelationInOperatorTree($folderRelationB, $foldersRelationsChangesDetails);
            if ($isFolderRelationAInOperatorTree && !$isFolderRelationBInOperatorTree) {
                return -1;
            } elseif (!$isFolderRelationAInOperatorTree && $isFolderRelationBInOperatorTree) {
                return 1;
            }
            // Otherwise most used relations should be applied in priority.
            if ($this->isFolderRelationMoreUsedThan($folderRelationA, $folderRelationB, $foldersRelationsChangesDetails)) {
                return -1;
            } elseif ($this->isFolderRelationMoreUsedThan($folderRelationB, $folderRelationA, $foldersRelationsChangesDetails)) {
                return 1;
            }
            if (!is_null($userId)) {
                $isFolderRelationAInUserTree = $this->isFolderRelationInUserTree($folderRelationA, $foldersRelationsChangesDetails);
                $isFolderRelationBInUserTree = $this->isFolderRelationInUserTree($folderRelationB, $foldersRelationsChangesDetails);
                if ($isFolderRelationAInUserTree && !$isFolderRelationBInUserTree) {
                    return -1;
                } elseif (!$isFolderRelationAInUserTree && $isFolderRelationBInUserTree) {
                    return 1;
                }
            }
            // Otherwise oldest relations should be applied in priority.
            if ($this->isFolderRelationOlderThan($folderRelationA, $folderRelationB, $foldersRelationsChangesDetails)) {
                return -1;
            } elseif ($this->isFolderRelationOlderThan($folderRelationB, $folderRelationA, $foldersRelationsChangesDetails)) {
                return 1;
            }

            return 0;
        });
    }

    private function getFolderRelationsDetails(array $foldersRelations, UserAccessControl $uac, ?string $userId = null)
    {
        if (empty($foldersRelations)) {
            return [];
        }

        $foldersRelationsArray = Hash::map($foldersRelations, '{n}', function (FoldersRelation $excludeFolderRelation) {
            return [
                'foreign_id' => $excludeFolderRelation->foreign_id,
                'folder_parent_id' => $excludeFolderRelation->folder_parent_id,
            ];
        });

        $foldersRelationsInOperatorTreeCollection = $this->foldersRelationsTable
            ->findByUserId($uac->getId())
            ->select([
                'foreign_id' => 'foreign_id',
                'folder_parent_id' => 'folder_parent_id',
            ])
            ->where(new TupleComparison(['foreign_id', 'folder_parent_id'], $foldersRelationsArray, [], 'IN'))
            ->all()
            ->combine([$this, 'getFolderRelationKey'], function () {
                return ['inOperatorTree' => true];
            })
            ->toArray();

        $foldersRelationsInUserTreeCollection = [];
        if (!is_null($userId)) {
            $foldersRelationsInUserTreeCollection = $this->foldersRelationsTable
                ->findByUserId($userId)
                ->select([
                    'foreign_id' => 'foreign_id',
                    'folder_parent_id' => 'folder_parent_id',
                ])
                ->where(new TupleComparison(['foreign_id', 'folder_parent_id'], $foldersRelationsArray, [], 'IN'))
                ->all()
                ->combine([$this, 'getFolderRelationKey'], function () {
                    return ['inUserTree' => true];
                })
                ->toArray();
        }

        $foldersRelationsUsageQuery = $this->foldersRelationsTable->find();
        $foldersRelationsUsageCollection = $foldersRelationsUsageQuery->select([
            'foreign_id' => 'foreign_id',
            'folder_parent_id' => 'folder_parent_id',
            'usage_count' => $foldersRelationsUsageQuery->func()->count('*'),
        ])
            ->where(new TupleComparison(['foreign_id', 'folder_parent_id'], $foldersRelationsArray, [], 'IN'))
            ->group(['foreign_id', 'folder_parent_id'])
            ->all()
            ->combine([$this, 'getFolderRelationKey'], function ($folderRelation) {
                return $folderRelation->extract(['usage_count']);
            })
            ->toArray();

        $foldersRelationsCreatedQuery = $this->foldersRelationsTable->find();
        $foldersRelationsCreatedCollection = $foldersRelationsCreatedQuery->select(['foreign_id' => 'foreign_id', 'folder_parent_id' => 'folder_parent_id', 'created_oldest' => 'MIN(created)'])
            ->where(new TupleComparison(['foreign_id', 'folder_parent_id'], $foldersRelationsArray, [], 'IN'))
            ->group(['foreign_id', 'folder_parent_id'])
            ->all()
            ->combine([$this, 'getFolderRelationKey'], function ($folderRelation) {
                return $folderRelation->extract(['created_oldest']);
            })
            ->toArray();

        return array_merge_recursive($foldersRelationsInOperatorTreeCollection, $foldersRelationsUsageCollection, $foldersRelationsInUserTreeCollection, $foldersRelationsCreatedCollection);
    }

    public function getFolderRelationKey(FoldersRelation $folderRelation): string
    {
        return "{$folderRelation->foreign_id} {$folderRelation->folder_parent_id}";
    }

    private function isFolderRelationInOperatorTree(FoldersRelation $folderRelation, array $foldersRelationsChangesDetails): bool
    {
        $folderRelationKey = $this->getFolderRelationKey($folderRelation);
        if (isset($foldersRelationsChangesDetails[$folderRelationKey]['inOperatorTree'])) {
            return $foldersRelationsChangesDetails[$folderRelationKey]['inOperatorTree'];
        }

        return false;
    }

    private function isFolderRelationInUserTree(FoldersRelation $folderRelation, array $foldersRelationsChangesDetails): bool
    {
        $folderRelationKey = $this->getFolderRelationKey($folderRelation);
        if (isset($foldersRelationsChangesDetails[$folderRelationKey]['inUserTree'])) {
            return $foldersRelationsChangesDetails[$folderRelationKey]['inUserTree'];
        }

        return false;
    }

    private function isFolderRelationMoreUsedThan(FoldersRelation $folderRelationA, FoldersRelation $folderRelationB, array $foldersRelationsChangesDetails): bool
    {
        $folderRelationAUsageCount = $folderRelationBUsageCount = 0;
        $folderRelationAKey = $this->getFolderRelationKey($folderRelationA);
        $folderRelationBKey = $this->getFolderRelationKey($folderRelationB);
        if (isset($foldersRelationsChangesDetails[$folderRelationAKey]['usage_count'])) {
            $folderRelationAUsageCount = $foldersRelationsChangesDetails[$folderRelationAKey]['usage_count'];
        }
        if (isset($foldersRelationsChangesDetails[$folderRelationBKey]['usage_count'])) {
            $folderRelationBUsageCount = $foldersRelationsChangesDetails[$folderRelationBKey]['usage_count'];
        }

        return $folderRelationAUsageCount > $folderRelationBUsageCount;
    }

    private function isFolderRelationOlderThan(FoldersRelation $folderRelationA, FoldersRelation $folderRelationB, array $foldersRelationsChangesDetails): bool
    {
        $folderRelationACreated = $folderRelationBCreated = 0;
        $folderRelationAKey = $this->getFolderRelationKey($folderRelationA);
        $folderRelationBKey = $this->getFolderRelationKey($folderRelationB);
        if (isset($foldersRelationsChangesDetails[$folderRelationAKey]['created_oldest'])) {
            $folderRelationACreated = $foldersRelationsChangesDetails[$folderRelationAKey]['created_oldest'];
        }
        if (isset($foldersRelationsChangesDetails[$folderRelationBKey]['created_oldest'])) {
            $folderRelationBCreated = $foldersRelationsChangesDetails[$folderRelationBKey]['created_oldest'];
        }

        return $folderRelationACreated < $folderRelationBCreated;
    }
}
