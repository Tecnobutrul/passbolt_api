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
namespace Passbolt\Sso\Utility\AuthToken;

use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
use InvalidArgumentException;
use Passbolt\Sso\Model\Entity\SsoState;

class SsoStateExpiry
{
    /**
     * @param string $type SSO state type.
     * @return string
     */
    public function getFromType(string $type): string
    {
        if (!in_array($type, SsoState::ALLOWED_SSO_STATE_TYPES)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid $type `%s`. Must be one of `%s`.',
                    $type,
                    implode(',', SsoState::ALLOWED_SSO_STATE_TYPES)
                )
            );
        }

        $typeExpiry = Configure::read(sprintf('passbolt.plugins.sso.expiry.%s', $type));

        if (!is_string($typeExpiry)) {
            $msg = 'No default expiry or expiry for token type ' . $typeExpiry;
            throw new InternalErrorException($msg);
        }

        return $typeExpiry;
    }
}
