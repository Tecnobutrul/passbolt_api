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
 * @since         2.11.0
 */

namespace Passbolt\MultiTenantAdmin\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;

/**
 * Auth Token Component
 */
class AuthTokenComponent extends Component
{
    /**
     * @var Request
     */
    protected $_request;

    /**
     * Initialize properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        $controller = $this->_registry->getController();
        $this->_request = $controller->getRequest();
    }

    /**
     * Authenticate the user.
     * @return bool
     * @throws ForbiddenException If the authentication token is not valid.
     */
    public function identify()
    {
        $authHeaderArr = $this->_request->getHeader('Authorization');
        if (empty($authHeaderArr) || !is_array($authHeaderArr)) {
            throw new ForbiddenException('You are not authorized to access this location');
        }

        $authHeader = $authHeaderArr[0];
        $authBase64 = str_replace('Basic ', '', $authHeader);
        $auth = explode(':', base64_decode($authBase64));

        if (count($auth) !== 2) {
            throw new ForbiddenException('You are not authorized to access this location');
        }

        $username = Configure::consume('passbolt.plugins.multiTenantAdmin.auth.username');
        $password = Configure::consume('passbolt.plugins.multiTenantAdmin.auth.password');

        if ($auth[0] !== $username || $auth[1] !== $password) {
            throw new ForbiddenException('You are not authorized to access this location');
        }

        return true;
    }
}
