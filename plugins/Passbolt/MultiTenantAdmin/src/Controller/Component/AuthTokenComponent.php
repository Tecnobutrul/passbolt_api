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
namespace Passbolt\MultiTenantAdmin\Controller\Component;

use Cake\Auth\DefaultPasswordHasher;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Utility\Security;

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
        $this->_request = $controller->request;
    }

    /**
     * Authenticate the user.
     * @return void
     * @throws ForbiddenException If the authentication token is not valid.
     */
    public function identify()
    {
        $authToken = $this->_request->query('auth_token');
        if (is_null($authToken)) {
            throw new ForbiddenException('You are not authorized to access this location');
        }

        // Is the authentication token valid.
        $authSecret = Configure::read('passbolt.multiTenant.auth.secret');

        //$token = '30f7f212-dfa7-4e94-8285-c8038e9a27bd';
        // The line below generates the secret.
        //var_dump(password_hash($token . Security::getSalt(),PASSWORD_ARGON2I));
        //var_dump((new DefaultPasswordHasher)->hash($token . Security::getSalt()));

        $isValidAuthToken = (new DefaultPasswordHasher)->check($authToken . Security::getSalt(), $authSecret);

        if (!$isValidAuthToken) {
            throw new ForbiddenException('You are not authorized to access this location');
        }
    }
}
