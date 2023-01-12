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

use Passbolt\Sso\Test\Factory\SsoSettingsFactory;
use Passbolt\Sso\Test\Lib\SsoIntegrationTestCase;

class SsoSettingsIndexControllerTest extends SsoIntegrationTestCase
{
    public function testSsoSettingsIndexController_SuccessAzure(): void
    {
        $this->markTestIncomplete();
    }

    public function testSsoSettingsIndexController_ErrorNotLoggedIn(): void
    {
        $ssoSetting = SsoSettingsFactory::make()->azure()->draft()->persist();
        $this->getJson('/sso/settings.json');
        $this->assertAuthenticationError();
    }

    public function testSsoSettingsIndexController_ErrorNotAdmin(): void
    {
        SsoSettingsFactory::make()->azure()->draft()->persist();
        $this->logInAsUser();
        $this->getJson('/sso/settings.json');
        $this->assertError(403);
    }
}
