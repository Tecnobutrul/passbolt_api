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
use Passbolt\Sso\Model\Entity\SsoAuthenticationToken;
use Passbolt\Sso\Service\Sso\Azure\SsoAzureService;
use Passbolt\Sso\Test\Factory\SsoAuthenticationTokenFactory;
use Passbolt\Sso\Test\Lib\SsoIntegrationTestCase;

class SsoAzureServiceTest extends SsoIntegrationTestCase
{
    public function testSsoAzureService_Success(): void
    {
        // Load default plugin config
        $this->loadPlugins(['Passbolt/Sso' => []]);

        $user = UserFactory::make()->admin()->active()->persist();
        $setting = $this->createAzureSettingsFromConfig($user);
        $uac = new ExtendedUserAccessControl($user->role->name, $user->id, $user->username, '127.0.0.1', 'phpunit');

        // Main service features = generate url + cookie
        $sut = new SsoAzureService();
        $url = $sut->getAuthorizationUrl($uac);
        $cookie = $sut->createStateCookie($uac);

        // Check token props
        /** @var SsoAuthenticationToken $token */
        $token = SsoAuthenticationTokenFactory::find()->firstOrFail();

        /**
         * @psalm-suppress RedundantCondition needed to avoid SsoAuthenticationToken is not used in this file sniff
         */
        $this->assertTrue($token instanceof SsoAuthenticationToken);

        $this->assertEquals($user->id, $token->user_id);
        $this->assertEquals('127.0.0.1', $token->getDataProperty('ip'));
        $this->assertEquals('phpunit', $token->getDataProperty('user_agent'));
        $this->assertEquals($setting->id, $token->getDataProperty('sso_setting_id'));

        // Check URL props
        $data = $setting->getData();
        $this->assertNotNull($data);
        $data = $data->toArray();
        $this->assertTrue(is_string($data['url']));
        $this->assertTrue(is_string($data['tenant_id']));
        $this->assertTrue(is_string($data['client_id']));
        $this->assertStringContainsString($data['url'] . '/', $url);
        $this->assertStringContainsString('/' . $data['tenant_id'] . '/', $url);
        $this->assertStringContainsString('client_id=' . $data['client_id'], $url);
        $this->assertStringContainsString('state=' . $token->token, $url);
        $this->assertStringContainsString('prompt=login', $url);
        $this->assertStringContainsString('login_hint=' . urlencode($user->username), $url);

        // Check cookie props
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals($token->token, $cookie->getValue());
    }

    public function testSsoAzureService_Error(): void
    {
        $this->markTestIncomplete();
    }
}
