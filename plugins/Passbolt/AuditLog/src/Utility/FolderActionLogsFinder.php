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
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class FolderActionLogsFinder extends BaseActionLogsFinder
{
    /**
     * Find ActionLog ids for a given FolderHistory folder id
     *
     * @param string $folderId folder id
     * @return \Cake\ORM\Query query
     */
    protected function _findActionLogIdsForFolders(string $folderId): Query
    {
        return $this->ActionLogs
            ->find()
            ->select(['ActionLogs__id' => 'ActionLogs.id'])
            ->contain(['EntitiesHistory.FoldersHistory'])
            ->innerJoinWith('EntitiesHistory.FoldersHistory')
            ->where([
                'FoldersHistory.folder_id' => $folderId,
                'ActionLogs.status' => 1,
            ])
            ->group('ActionLogs.id');
    }

    /**
     * Find ActionLog ids for a given PermissionHistory folder id
     *
     * @param string $folderId folder id
     * @return \Cake\ORM\Query query
     */
    protected function _findActionLogIdsForPermissionsHistoryFolders(string $folderId): Query
    {
        return $this->ActionLogs
            ->find()
            ->select(['ActionLogs__id' => 'ActionLogs.id'])
            ->contain(['EntitiesHistory.PermissionsHistory'])
            ->innerJoinWith('EntitiesHistory.PermissionsHistory')
            ->contain(['EntitiesHistory.PermissionsHistory.PermissionsHistoryFolders'])
            ->innerJoinWith('EntitiesHistory.PermissionsHistory.PermissionsHistoryFolders')
            ->where([
                'PermissionsHistoryFolders.id' => $folderId,
                'ActionLogs.status' => 1,
            ])
            ->group('ActionLogs.id');
    }

    /**
     * Filter a query by folder id
     *
     * @param \Cake\ORM\Query $query The target query
     * @param string $folderId The target folder
     * @return \Cake\ORM\Query
     */
    protected function _filterQueryByFolderId(Query $query, string $folderId): Query
    {
        $subQuery = $this->_findActionLogIdsForFolders($folderId)
            ->union($this->_findActionLogIdsForPermissionsHistoryFolders($folderId));

        $query->join([
            'folderActionLogs' => [
                'table' => $subQuery,
                'alias' => 'folderActionLogs',
                'type' => 'INNER',
                'conditions' => ['folderActionLogs.ActionLogs__id = ActionLogs.id'],
            ],
        ]);

        $query->order([
            'ActionLogs.created' => 'DESC',
        ]);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function find(UserAccessControl $uac, string $entityId, ?array $options = []): array
    {
        if (!Configure::read('passbolt.plugins.folders.enabled')) {
            return [];
        }

        // Check that the folder exists and is accessible.
        /** @var \Passbolt\Folders\Model\Table\FoldersTable $Folders */
        $Folders = TableRegistry::getTableLocator()->get('Passbolt/Folders.Folders');
        $folder = $Folders->findView($uac->getId(), $entityId, $options)->first();

        if (empty($folder)) {
            throw new NotFoundException('The folder does not exist.');
        }

        // Build query.
        $q = $this->_getBaseQuery();
        $q = $this->_filterQueryByFolderId($q, $entityId);
        if (!empty($options)) {
            $q = $this->_paginate($q, $options);
        }
        $actionLogs = $q->all();
        $resultParser = new ActionLogResultsParser($actionLogs, ['folders' => [$entityId]]);

        return $resultParser->parse();
    }
}
