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

namespace Passbolt\Folders\Service\Resources;

use App\Model\Entity\Permission;
use App\Model\Entity\Resource;
use App\Model\Table\PermissionsTable;
use App\Utility\UserAccessControl;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Exception\NotFoundException;
use Passbolt\Folders\Model\Behavior\FolderizableBehavior;
use Passbolt\Folders\Model\Entity\FoldersRelation;
use Passbolt\Folders\Service\FoldersRelations\FoldersRelationsAddItemToUserTreeService;

class ResourcesAfterAccessGrantedService
{
    use ModelAwareTrait;

    /**
     * @var \App\Model\Table\GroupsUsersTable
     */
    private $GroupsUsers;

    /**
     * @var \App\Model\Table\ResourcesTable
     */
    private $Resources;

    /**
     * @var \Passbolt\Folders\Service\FoldersRelations\FoldersRelationsAddItemToUserTreeService
     */
    private $foldersRelationsAddItemToUserTree;

    /**
     * Instantiate the service.
     */
    public function __construct()
    {
        $this->loadModel('GroupsUsers');
        $this->loadModel('Resources');

        $this->foldersRelationsAddItemToUserTree = new FoldersRelationsAddItemToUserTreeService();
    }

    /**
     * Handle a granted access on a resource.
     *
     * @param \App\Utility\UserAccessControl $uac The operator
     * @param \App\Model\Entity\Permission $permission The granted permission
     * @return void
     * @throws \Exception
     */
    public function afterAccessGranted(UserAccessControl $uac, Permission $permission): void
    {
        $resource = $this->getResource($uac, $permission->aco_foreign_key);

        if ($permission->aro === PermissionsTable::GROUP_ARO) {
            $this->addResourceToGroupUsersTrees($uac, $resource, $permission->aro_foreign_key);
        } else {
            $this->addResourceToUserTree($uac, $resource, $permission->aro_foreign_key);
        }
    }

    /**
     * Retrieve the resource.
     *
     * @param \App\Utility\UserAccessControl $uac UserAccessControl updating the resource
     * @param string $resourceId The resource identifier to retrieve.
     * @return \App\Model\Entity\Resource
     * @throws \Cake\Http\Exception\NotFoundException If the resource does not exist.
     */
    private function getResource(UserAccessControl $uac, string $resourceId): Resource
    {
        try {
            return $this->Resources->get($resourceId, [
                'finder' => FolderizableBehavior::FINDER_NAME,
                'user_id' => $uac->getId(),
            ]);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('The resource does not exist.'));
        }
    }

    /**
     * Add a resource to a group of users trees.
     *
     * @param \App\Utility\UserAccessControl $uac The operator
     * @param \App\Model\Entity\Resource $resource The target resource
     * @param string $groupId The target group
     * @return void
     * @throws \Exception If something wrong occurred
     */
    private function addResourceToGroupUsersTrees(UserAccessControl $uac, Resource $resource, string $groupId): void
    {
        $grousUsersIds = $this->GroupsUsers->findByGroupId($groupId)
            ->all()
            ->extract('user_id')
            ->toArray();
        foreach ($grousUsersIds as $groupUserId) {
            $this->addResourceToUserTree($uac, $resource, $groupUserId);
        }
    }

    /**
     * Add a resource to a user tree.
     *
     * @param \App\Utility\UserAccessControl $uac The operator
     * @param \App\Model\Entity\Resource $resource The target resource
     * @param string $userId The target user
     * @return void
     * @throws \Exception If something wrong occurred
     */
    private function addResourceToUserTree(UserAccessControl $uac, Resource $resource, string $userId): void
    {
        $foreignModel = FoldersRelation::FOREIGN_MODEL_RESOURCE;
        $this->foldersRelationsAddItemToUserTree->addItemToUserTree($uac, $foreignModel, $resource->id, $userId);
    }
}
