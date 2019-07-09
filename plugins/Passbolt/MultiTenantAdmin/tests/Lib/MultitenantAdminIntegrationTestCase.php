<?php

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
 * @since         2.11.0
 */

namespace Passbolt\MultiTenantAdmin\Test\Lib;

use App\Test\Lib\Utility\ErrorTrait;
use App\Test\Lib\Utility\JsonRequestTrait;
use App\Test\Lib\Utility\ObjectTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestCase;
use Cake\TestSuite\IntegrationTestTrait;

abstract class MultitenantAdminIntegrationTestCase extends IntegrationTestCase
{
    use ErrorTrait;
    use IntegrationTestTrait;
    use JsonRequestTrait;
    use ObjectTrait;

    /**
     * Setup.
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('passbolt.plugins.tags.enabled', false);
        Configure::write('passbolt.plugins.multiFactorAuthentication.enabled', false);
        Configure::write('passbolt.plugins.log.enabled', false);
    }

    /**
     * Set basic authorization header.
     */
    public function setBasicAuth()
    {
        $username = env('PASSBOLT_PLUGINS_MULTITENANTADMIN_AUTH_USERNAME', '__USERNAME__');
        $password = env('PASSBOLT_PLUGINS_MULTITENANTADMIN_AUTH_PASSWORD', '__PASSWORD__');
        $authHeader = 'Basic ' . base64_encode("$username:$password");
        $this->_request['headers']['Authorization'] = $authHeader;
    }
}
