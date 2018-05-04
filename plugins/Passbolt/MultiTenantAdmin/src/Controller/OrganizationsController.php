<?php
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
 * @since         2.0.0
 */
namespace Passbolt\MultiTenantAdmin\Controller;

use Cake\Core\Exception\Exception;
use Cake\Network\Exception\BadRequestException;
use Cake\Routing\Router;
use Cake\Validation\Validation;
use Passbolt\MultiTenantAdmin\Model\Entity\Organization;
use Passbolt\MultiTenantAdmin\Utility\OrganizationManager;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class OrganizationsController extends MultiTenantAdminController
{
    /**
     * Add an organization in the cloud.
     * parameters:
     * organization => [
     *   slug
     *   plan
     *   max_users
     * ],
     * user => [
     *   username,
     *   profile => [
     *     first_name,
     *     last_name
     *   ]
     * ]
     *
     * @return mixed
     */
    public function add()
    {
        $this->loadModel('Passbolt/MultiTenantAdmin.Organizations');
        $this->viewBuilder()->setClassName('LegacyJson');

        if (empty($this->request->getData())) {
            return $this->error('Invalid request');
        }

        $data = $this->request->getData();

//        $data = [
//            'organization' => [
//                'slug' => 'acme',
//                'plan' => 'trial',
//                'max_users' => 2
//            ],
//            'user' => [
//                'username' => 'kevin@passbolt.com',
//                'profile' => [
//                    'first_name' => 'kevin',
//                    'last_name' => 'muller',
//                ]
//            ]
//        ];

        try {
            $organization = $this->_buildAndValidateOrganizationEntity($data['organization']);
            if (!$this->Organizations->save($organization)) {
                $this->_handleValidationErrors($organization);
            }
        } catch (BadRequestException $e) {
            return $this->error($e->getMessage());
        }

        try {
            // Add organization.
            $organizationManager = new OrganizationManager($data['organization']['slug']);
            $organizationManager->add();
            $userData = $organizationManager->addAdminUser($data['user']);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        $setupUrl = Router::url('/setup/install/' . $userData['user']->id . '/' . $userData['token']->token, true);

        $res = [
            'organization' => $organization,
            'user' => $userData['user'],
            'setupUrl' => $setupUrl
        ];

        $this->success('The organization has been created', $res);
    }

    /**
     * View organization, by slug or id.
     * @param string $id uuid or slug
     * @return mixed
     */
    public function view($id)
    {
        $this->loadModel('Passbolt/MultiTenantAdmin.Organizations');

        // Check request sanity
        $searchField = 'id';
        if (!Validation::uuid($id)) {
            $searchField = 'slug';
        }

        $organization = $this->Organizations->find()
            ->where([
                $searchField => $id
            ])
            ->first();

        $this->viewBuilder()->setClassName('LegacyJson');
        if (empty($organization)) {
            return $this->error("Not found");
        }

        $this->success('Organization found', $organization);
    }

    /**
     * Build and validate organization entity.
     *
     * @param array $data data
     *
     * @return Passbolt\MultiTenantAdmin\Model\Entity $organization organization entity
     */
    protected function _buildAndValidateOrganizationEntity($data)
    {
        // Build entity and perform basic check.
        $organization = $this->Organizations->newEntity(
            $data,
            [
                'accessibleFields' => [
                    'slug' => true,
                    'plan' => true,
                    'max_users' => true,
                ],
            ]
        );

        //var_dump($organization); die();

        $this->_handleValidationErrors($organization);

        return $organization;
    }

    /**
     * Manage validation errors.
     *
     * @param  \Passbolt\MultiTenantAdmin\Model\Entity\Organization $organization organization
     * @throws BadRequestException
     * @throws NotFoundException
     * @return void
     */
    protected function _handleValidationErrors(Organization $organization)
    {
        $errors = $organization->getErrors();
        if (!empty($errors)) {
            throw new BadRequestException(__('Could not validate organization data.'));
        }
    }
}
