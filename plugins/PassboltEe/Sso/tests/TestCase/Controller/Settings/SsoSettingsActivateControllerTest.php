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

use App\Test\Factory\UserFactory;
use App\Utility\UuidFactory;
use Passbolt\Sso\Model\Entity\SsoSetting;
use Passbolt\Sso\Test\Factory\SsoSettingsFactory;
use Passbolt\Sso\Test\Factory\SsoStateFactory;
use Passbolt\Sso\Test\Lib\SsoIntegrationTestCase;

class SsoSettingsActivateControllerTest extends SsoIntegrationTestCase
{
    public function testSsoSettingsActivateController_SuccessAzure(): void
    {
        $settings = SsoSettingsFactory::make()->azure()->persist();
        $user = UserFactory::make()->admin()->active()->persist();
        $ssoState = SsoStateFactory::make(['ip' => '127.0.0.1', 'user_agent' => 'phpunit'])
            ->withTypeSsoSetSettings()
            ->userId($user->id)
            ->ssoSettingsId($settings->id)
            ->persist();
        $this->logInAs($user);

        $this->postJson('/sso/settings/' . $settings->id . '.json', [
            'status' => SsoSetting::STATUS_ACTIVE,
            'token' => $ssoState->state,
        ]);

        $this->assertSuccess();
        $settingDto = $this->_responseJsonBody;
        $this->assertEquals($settingDto->id, $settings->id);
    }

    public function testSsoSettingsActivateController_ErrorNotLoggedIn(): void
    {
        $settingsId = UuidFactory::uuid();
        $this->postJson('/sso/settings/' . $settingsId . '.json', []);
        $this->assertAuthenticationError();
    }

    public function testSsoSettingsActivateController_ErrorNotAdmin(): void
    {
        $this->logInAsUser();
        $settingsId = UuidFactory::uuid();
        $this->postJson('/sso/settings/' . $settingsId . '.json', []);
        $this->assertError(403);
    }

    public function testSsoSettingsActivateController_ErrorInvalidId(): void
    {
        $this->logInAsAdmin();
        $settingsId = 'nope';
        $this->postJson('/sso/settings/' . $settingsId . '.json', []);
        $this->assertError(400);
    }

    public function testSsoSettingsActivateController_ErrorMissingData(): void
    {
        $this->logInAsAdmin();
        $settingsId = UuidFactory::uuid();
        $this->postJson('/sso/settings/' . $settingsId . '.json', []);
        $this->assertError(400);
    }

    public function testSsoSettingsActivateController_ErrorValidationStatus(): void
    {
        $this->logInAsAdmin();
        $settingsId = UuidFactory::uuid();
        $this->postJson('/sso/settings/' . $settingsId . '.json', [
            'status' => SsoSetting::STATUS_DRAFT,
            'token' => UuidFactory::uuid()]);
        $this->assertError(400);
    }

    public function testSsoSettingsActivateController_ErrorValidationToken(): void
    {
        $settings = SsoSettingsFactory::make()->azure()->persist();
        $this->logInAsAdmin();
        $this->postJson('/sso/settings/' . $settings->id . '.json', [
            'status' => SsoSetting::STATUS_ACTIVE,
            'token' => UuidFactory::uuid()]);
        $this->assertError(400);
    }
}
