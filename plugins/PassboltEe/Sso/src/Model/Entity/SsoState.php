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

namespace Passbolt\Sso\Model\Entity;

use Cake\ORM\Entity;

/**
 * SsoState Entity
 *
 * @property string $id
 * @property string $nonce
 * @property string $type
 * @property string $state
 * @property string $sso_settings_id
 * @property string|null $user_id
 * @property string $user_agent
 * @property string $ip
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime|null $deleted
 *
 * @property \Passbolt\Sso\Model\Entity\SsoSetting $sso_setting
 * @property \App\Model\Entity\User $user
 */
class SsoState extends Entity
{
    /**
     * Types
     */
    public const TYPE_SSO_STATE = 'sso_state';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'nonce' => false,
        'type' => false,
        'state' => false,
        'sso_settings_id' => false,
        'user_id' => false,
        'user_agent' => false,
        'ip' => false,
        'created' => false,
        'deleted' => false,
        'sso_setting' => false,
        'user' => false,
    ];
}
