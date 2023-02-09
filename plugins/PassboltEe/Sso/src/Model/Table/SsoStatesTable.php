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
 * @since         3.11.0
 */

namespace Passbolt\Sso\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SsoStates Model
 *
 * @property \Passbolt\Sso\Model\Table\SsoSettingsTable&\Cake\ORM\Association\BelongsTo $SsoSettings
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \Passbolt\Sso\Model\Entity\SsoState newEmptyEntity()
 * @method \Passbolt\Sso\Model\Entity\SsoState newEntity(array $data, array $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState[] newEntities(array $data, array $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState get($primaryKey, $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Passbolt\Sso\Model\Entity\SsoState[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SsoStatesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sso_states');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SsoSettings', [
            'foreignKey' => 'sso_settings_id',
            'joinType' => 'INNER',
            'className' => 'PassboltEe/Sso.SsoSettings',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'Users',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('nonce')
            ->maxLength('nonce', 64)
            ->requirePresence('nonce', 'create')
            ->notEmptyString('nonce')
            ->add('nonce', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('type')
            ->maxLength('type', 16)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('state')
            ->maxLength('state', 64)
            ->requirePresence('state', 'create')
            ->notEmptyString('state')
            ->add('state', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->uuid('sso_settings_id')
            ->notEmptyString('sso_settings_id');

        $validator
            ->uuid('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->scalar('user_agent')
            ->maxLength('user_agent', 255)
            ->requirePresence('user_agent', 'create')
            ->notEmptyString('user_agent');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 45)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['nonce']), ['errorField' => 'nonce']);
        $rules->add($rules->isUnique(['state']), ['errorField' => 'state']);
        $rules->add($rules->existsIn('sso_settings_id', 'SsoSettings'), ['errorField' => 'sso_settings_id']);
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
