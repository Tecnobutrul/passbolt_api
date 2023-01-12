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
 * @since         3.9.0
 */

namespace Passbolt\Sso\Test\TestCase\Service\Sso;

use App\Test\Factory\UserFactory;
use App\Utility\ExtendedUserAccessControl;
use Passbolt\Sso\Test\Lib\SsoTestCase;

class AbstractSsoAzureServiceTest extends SsoTestCase
{
    public function testSsoAbstractSsoAzureService_createHttpOnlySecureCookie(): void
    {
        $user = UserFactory::make()->active()->persist();
        $uac = new ExtendedUserAccessControl($user->role->name, $user->id, $user->username, '127.0.0.1', 'phpunit');
        $sut = new TestableSsoService();
        $cookie = $sut->createStateCookie($uac);

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
    }
}
