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

namespace Passbolt\Sso\Test\TestCase\Utility\Azure\Provider;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Passbolt\Sso\Test\Lib\AzureProviderTestTrait;
use Passbolt\Sso\Utility\Provider\BaseOauth2Provider;

class AzureProviderTest extends TestCase
{
    use AzureProviderTestTrait;

    public function setup(): void
    {
        $azureConfig = Configure::read('passbolt.selenium.sso.active');
        if (!isset($azureConfig)) {
            $this->markTestSkipped('Selenium SSO is set to inactive, skipping tests.');
        }
    }

    public function testSsoAzureProvider_ExtendsBaseOauth2Provider(): void
    {
        $this->assertInstanceOf(BaseOauth2Provider::class, $this->getDummyAzureProvider());
    }

    public function testSsoAzureProvider_getBaseAuthorizationUrl(): void
    {
        $provider = $this->getDummyAzureProvider();
        $url = $provider->getBaseAuthorizationUrl();
        $this->assertStringContainsString('authorize', $url);
    }

    public function testSsoAzureProvider_getBaseAccessTokenUrl(): void
    {
        $provider = $this->getDummyAzureProvider();
        $url = $provider->getBaseAccessTokenUrl([]);
        $this->assertStringContainsString('token', $url);
    }

    public function testSsoAzureProvider_getOpenIdBaseUri(): void
    {
        $provider = $this->getDummyAzureProvider();
        $url = $provider->getOpenIdBaseUri();
        $this->assertStringContainsString('microsoft', $url);
        $this->assertStringContainsString('v2.0', $url);
    }

    public function testSsoAzureProvider_getOpenIdConfigurationUri(): void
    {
        $provider = $this->getDummyAzureProvider();
        $url = $provider->getOpenIdConfigurationUri();
        $this->assertStringContainsString('.well-known', $url);
    }

    public function testSsoAzureProvider_getTenant(): void
    {
        $provider = $this->getDummyAzureProvider();
        $this->assertEquals(Configure::read('passbolt.selenium.sso.azure.tenantId'), $provider->getTenant());
    }

    public function testSsoAzureProvider_getClientId(): void
    {
        $provider = $this->getDummyAzureProvider();
        $this->assertEquals(Configure::read('passbolt.selenium.sso.azure.clientId'), $provider->getClientId());
    }
}
