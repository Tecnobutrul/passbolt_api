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
namespace Passbolt\Sso\Test\TestCase\Controller\Settings;

use Cake\Validation\Validation;
use Passbolt\Sso\Model\Entity\SsoSetting;
use Passbolt\Sso\Test\Factory\SsoSettingsFactory;
use Passbolt\Sso\Test\Lib\SsoIntegrationTestCase;

class SsoSettingsViewControllerTest extends SsoIntegrationTestCase
{
    public function testSsoSettingsViewController_SuccessAzure(): void
    {
        $ssoSetting = SsoSettingsFactory::make()->azure()->draft()->persist();
        $this->logInAsAdmin();
        $this->getJson('/sso/settings/' . $ssoSetting->id . '.json');
        $this->assertSuccess();
        $body = $this->_responseJsonBody;

        $this->assertTrue(Validation::uuid($body->id));
        $this->assertEquals(SsoSetting::PROVIDER_AZURE, $body->provider);
        $this->assertEquals(SsoSetting::ALLOWED_PROVIDERS, $body->providers);
        $this->assertEquals(SsoSetting::STATUS_DRAFT, $body->status);
        $this->assertEquals('https://login.microsoftonline.com', $body->data->url);
        $this->assertTrue(Validation::uuid($body->data->client_id));
        $this->assertTrue(Validation::uuid($body->data->tenant_id));
        $this->assertTrue(is_string($body->data->client_secret));
        $this->assertTrue(is_string($body->data->client_secret_expiry));
    }

    public function testSsoSettingsViewController_ErrorNotLoggedIn(): void
    {
        $ssoSetting = SsoSettingsFactory::make()->azure()->draft()->persist();
        $this->getJson('/sso/settings/' . $ssoSetting->id . '.json');
        $this->assertAuthenticationError();
    }

    public function testSsoSettingsViewController_ErrorNotAdmin(): void
    {
        $ssoSetting = SsoSettingsFactory::make()->azure()->draft()->persist();
        $this->logInAsUser();
        $this->getJson('/sso/settings/' . $ssoSetting->id . '.json');
        $this->assertError(403);
    }
}
