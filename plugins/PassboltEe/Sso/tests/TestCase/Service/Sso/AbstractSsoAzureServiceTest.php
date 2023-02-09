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
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Passbolt\Sso\Model\Entity\SsoState;
use Passbolt\Sso\Test\Lib\SsoTestCase;
use Passbolt\Sso\Utility\OpenId\ResourceOwnerWithEmailInterface;

class AbstractSsoAzureServiceTest extends SsoTestCase
{
    public function testSsoAbstractSsoAzureService_createHttpOnlySecureCookie(): void
    {
        Configure::write(
            sprintf('passbolt.plugins.sso.expiry.%s', SsoState::TYPE_SSO_STATE),
            '10 minutes'
        );
        $user = UserFactory::make()->active()->persist();
        $uac = new ExtendedUserAccessControl($user->role->name, $user->id, $user->username, '127.0.0.1', 'phpunit');
        $sut = new TestableSsoService();
        $cookie = $sut->createStateCookie($uac);

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
    }

    public function testSsoAbstractSsoAzureService_assertResourceOwnerAgainstUser_Success()
    {
        $username = 'email@test.test';
        $user = UserFactory::make()->setField('username', $username)->getEntity();
        $mixedCases = ['email@test.test', 'email@TEST.TEST', 'EMAIL@TEST.TEST'];
        $sut = new TestableSsoService();
        foreach ($mixedCases as $case) {
            $resourceOwner = $this->getMockBuilder(ResourceOwnerWithEmailInterface::class)->getMock();
            $resourceOwner->method('getEmail')->willReturn($case);
            $sut->assertResourceOwnerAgainstUser($resourceOwner, $user);
            $this->assertTrue(true);
        }
    }

    public function testSsoAbstractSsoAzureService_assertResourceOwnerAgainstUser_Comparison_Fail()
    {
        $username = 'email@test.test';
        $user = UserFactory::make()->setField('username', $username)->getEntity();
        $sut = new TestableSsoService();
        $resourceOwner = $this->getMockBuilder(ResourceOwnerWithEmailInterface::class)->getMock();
        $resourceOwner->method('getEmail')->willReturn('different@test.test');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Single sign-on failed. Username mismatch.');
        $sut->assertResourceOwnerAgainstUser($resourceOwner, $user);
    }
}
