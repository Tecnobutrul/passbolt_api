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
namespace Passbolt\MultiTenantAdmin\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrganizationsTable extends Table
{
    /**
     * Initialize
     * @param array $config config
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('organizations');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->regex('slug', '/^[a-z0-9]+[a-z0-9\-_]*[a-z0-9]+$/i', __('The slug format is incorrect.'))
            ->minLength('slug', 2, __('The slug length should be minimum {0} characters', 2))
            ->maxLength('slug', 128, __('The slug length should be maximum {0} characters.', 128))
            ->notEquals('slug', 'multi_tenant', __('This slug is reserved'))
            ->requirePresence('slug', 'create', __('A slug is required.'))
            ->notEmpty('slug', __('The slug cannot be empty.'));

        $validator
            ->utf8Extended('plan', __('The plan is not a valid utf8 string.'))
            ->inList('plan', ['trial', 'paid'], __('The plan is not supported'))
            ->requirePresence('plan', 'create', __('A plan is required.'))
            ->notEmpty('plan');

        $validator
            ->integer('max_users', __('max_users should be an integer'))
            ->greaterThan('max_users', 0, __('max_users should be greater than 1'))
            ->lessThan('max_users', 10000, __('max users should be less than 10000'))
            ->notEmpty('max_users');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['slug']), 'uniqueSlug', [
            'message' => __('This slug is already in use.')
        ]);

        return $rules;
    }
}
