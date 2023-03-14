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
 * @since         4.0.0
 */
namespace Passbolt\Sso\Utility\OpenId;

interface IdTokenInterface
{
    /**
     * Get JWT verification keys from SSO provider.
     *
     * @return array
     */
    public function getJwtVerificationKeys();

    /**
     * Returns client ID.
     *
     * @return string
     */
    public function getClientId(): string;

    /**
     * Returns base Open ID URI.
     *
     * @return string
     */
    public function getOpenIdBaseUri(): string;
}
