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

namespace Passbolt\Sso\Test\TestCase\Controller\Azure;

use App\Test\Factory\UserFactory;
use App\Utility\UuidFactory;
use Passbolt\Sso\Model\Entity\SsoAuthenticationToken;
use Passbolt\Sso\Test\Factory\SsoAuthenticationTokenFactory;
use Passbolt\Sso\Test\Factory\SsoSettingsFactory;
use Passbolt\Sso\Test\Lib\SsoIntegrationTestCase;

class SsoAzureStage2ControllerTest extends SsoIntegrationTestCase
{
    public function testSsoAzureStage2Controller_Success(): void
    {
        // Requires mocking AzureService - not implemented
        $this->markTestIncomplete();
    }

    /**
     * 400 if state is missing from url
     */
    public function testSsoAzureStage2Controller_Common_ErrorStateFromUrlMissing(): void
    {
        $this->get('/sso/azure/redirect');
        $this->assertResponseCode(400);
        $this->assertResponseContains('The state is required in URL parameters.');
    }

    /**
     * 400 if state from url is not UUID
     */
    public function testSsoAzureStage2Controller_Common_ErrorStateFromUrlInvalid(): void
    {
        $this->get('/sso/azure/redirect?state=nope');
        $this->assertResponseCode(400);
        $this->assertResponseContains('The state is required in URL parameters.');
    }

    /**
     * 400 if state from url is not UUID
     */
    public function testSsoAzureStage2Controller_Common_ErrorStateFromCookieInvalid(): void
    {
        $this->cookie('passbolt_sso_state', 'nope');
        $this->get('/sso/azure/redirect?state=' . UuidFactory::uuid());
        $this->assertResponseCode(400);
        $this->assertResponseContains('The state is required in cookie.');
    }

    /**
     * 400 if state from url is not UUID
     */
    public function testSsoAzureStage2Controller_Common_ErrorStateMismatch(): void
    {
        $this->cookie('passbolt_sso_state', UuidFactory::uuid());
        $this->get('/sso/azure/redirect?state=' . UuidFactory::uuid());
        $this->assertResponseCode(400);
        $this->assertResponseContains('CSRF issue');
    }

    /**
     * 400 if state from url is not UUID
     */
    public function testSsoAzureStage2Controller_Common_ErrorCodeMissing(): void
    {
        $uuid = UuidFactory::uuid();
        $this->cookie('passbolt_sso_state', $uuid);
        $this->get('/sso/azure/redirect?state=' . $uuid);
        $this->assertResponseCode(400);
        $this->assertResponseContains('The code is required in URL parameters.');
    }

    /**
     * 400 if state from url is not UUID
     */
    public function testSsoAzureStage2Controller_Common_ErrorCodeInvalid(): void
    {
        $uuid = UuidFactory::uuid();
        $this->cookie('passbolt_sso_state', $uuid);
        $this->get('/sso/azure/redirect?state=' . $uuid . '&code[not]=string');
        $this->assertResponseCode(400);
        $this->assertResponseContains('The code is required in URL parameters.');
    }

    // ADMIN TESTS

    /**
     * 400 if state from url is not UUID
     */
    public function testSsoAzureStage2Controller_Admin_ErrorInvalidToken(): void
    {
        $admin = UserFactory::make()->admin()->active()->persist();
        $this->logInAs($admin);

        $uuid = UuidFactory::uuid();
        $this->cookie('passbolt_sso_state', $uuid);
        $this->get('/sso/azure/redirect?state=' . $uuid . '&code=' . $uuid);
        $this->assertResponseCode(400);
        $this->assertResponseContains('The authentication token does not exist.');
    }

    /**
     * 400 if not draft settings
     */
    public function testSsoAzureStage2Controller_Admin_ErrorNotDraftSettings(): void
    {
        $admin = UserFactory::make()->admin()->active()->persist();
        $settings = SsoSettingsFactory::make()->azure()->active()->persist();
        $token = SsoAuthenticationTokenFactory::make()
            ->type(SsoAuthenticationToken::TYPE_SSO_STATE)
            ->userId($admin->id)
            ->active()
            ->data([
                'sso_setting_id' => $settings->id,
                'ip' => SsoIntegrationTestCase::IP_ADDRESS,
                'user_agent' => 'phpunit',
            ])
            ->persist();

        $this->logInAs($admin);

        $this->cookie('passbolt_sso_state', $token->token);
        $this->get('/sso/azure/redirect?state=' . $token->token . '&code=' . UuidFactory::uuid());
        $this->assertResponseCode(400);
        $this->assertResponseContains('The SSO settings do not exist.');
    }

    /**
     * 400 if token is invalid
     */
    public function testSsoAzureStage2Controller_Admin_ErrorInvalidTokenUseragent(): void
    {
        $admin = UserFactory::make()->admin()->active()->persist();
        $settings = SsoSettingsFactory::make()->azure()->draft()->persist();
        $token = SsoAuthenticationTokenFactory::make()
            ->type(SsoAuthenticationToken::TYPE_SSO_STATE)
            ->userId($admin->id)
            ->active()
            ->data([
                'sso_setting_id' => $settings->id,
                'ip' => SsoIntegrationTestCase::IP_ADDRESS,
                'user_agent' => 'something else',
            ])
            ->persist();

        $this->logInAs($admin);

        $this->cookie('passbolt_sso_state', $token->token);
        $this->get('/sso/azure/redirect?state=' . $token->token . '&code=' . UuidFactory::uuid());
        $this->assertResponseCode(400);
        $this->assertResponseContains('The SSO authentication token is invalid. User agent mismatch.');
    }

    // USERS TESTS

    /**
     * 400 if
     */
    public function testSsoAzureStage2Controller_User_ErrorIsLoggedIn(): void
    {
        $user = UserFactory::make()->user()->active()->persist();
        $this->logInAs($user);

        $uuid = UuidFactory::uuid();
        $this->cookie('passbolt_sso_state', $uuid);
        $this->get('/sso/azure/redirect?state=' . $uuid . '&code=' . UuidFactory::uuid());
        $this->assertResponseCode(403);
        $this->assertResponseContains('The user should not be logged in.');
    }
}
