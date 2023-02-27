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
use Passbolt\Sso\Model\Entity\SsoState;
use Passbolt\Sso\Service\Sso\Azure\SsoAzureService;
use Passbolt\Sso\Test\Factory\SsoStateFactory;
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
        $cookie = $sut->createStateCookie($uac, SsoState::TYPE_SSO_SET_SETTINGS);

        // Check state & nonce values are present
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('nonce=', $url);

        // Check SSO state props
        /** @var \Passbolt\Sso\Model\Entity\SsoState $ssoState */
        $ssoState = SsoStateFactory::find()->firstOrFail();
        $this->assertInstanceOf(SsoState::class, $ssoState);
        $this->assertEquals($user->id, $ssoState->user_id);
        $this->assertEquals('127.0.0.1', $ssoState->ip);
        $this->assertEquals('phpunit', $ssoState->user_agent);
        $this->assertEquals($setting->id, $ssoState->sso_settings_id);

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
        $this->assertStringContainsString('state=' . $ssoState->state, $url);
        $this->assertStringContainsString('prompt=login', $url);
        $this->assertStringContainsString('login_hint=' . urlencode($user->username), $url);

        // Check cookie props
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals($ssoState->state, $cookie->getValue());
    }

    public function testSsoAzureService_Error(): void
    {
        $this->markTestIncomplete();
    }
}
