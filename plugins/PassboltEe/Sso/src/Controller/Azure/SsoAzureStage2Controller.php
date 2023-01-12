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

namespace Passbolt\Sso\Controller\Azure;

use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Routing\Router;
use Passbolt\Sso\Controller\AbstractSsoController;
use Passbolt\Sso\Service\Sso\Azure\SsoAzureService;
use Passbolt\Sso\Service\SsoSettings\SsoSettingsGetService;

class SsoAzureStage2Controller extends AbstractSsoController
{
    /**
     * @inheritDoc
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['triage']);
    }

    /**
     * Handle both user is admin and trying to validate a setting or regular user SSO return
     *
     * @return void
     */
    public function triage(): void
    {
        // Get state from cookie and URL to prevent CSRF
        $state = $this->getStateFromUrlAndCookie();

        // Handle any error code
        $this->assertErrorFromUrlQuery();

        // Check that there is a code in the URL query
        $code = $this->getCodeFromUrlQuery();

        if ($this->User->isAdmin()) {
            $this->stage2AsAdmin($state, $code);
        } else {
            $this->stage2($state, $code);
        }
    }

    /**
     * @param string $state uuid
     * @param string $code jwt
     * @return void
     */
    public function stage2AsAdmin(string $state, string $code): void
    {
        // Get the settings from the authentication token data
        try {
            $settingsDto = (new SsoSettingsGetService())->getDraftSettingFromTokenOrFail($state);
        } catch (\Exception $exception) {
            throw new BadRequestException($exception->getMessage(), 400, $exception);
        }

        $service = new SsoAzureService($settingsDto);
        $uac = $service->assertStateCodeAndGetUac($state, $code, $this->User->ip(), $this->User->userAgent());

        // Create token for next step, e.g. activate settings
        $token = $service->createSsoAuthTokenToActiveSettings($uac, $service->getSettings()->id);

        $this->response = $this->getResponse()->withCookie($service->clearStateCookie());
        $this->redirect(Router::url('/sso/login/dry-run/success?token=') . $token->token);
    }

    /**
     * @param string $state uuid
     * @param string $code jwt
     * @return void
     */
    public function stage2(string $state, string $code): void
    {
        $this->User->assertNotLoggedIn();

        // Get settings from token id
        // if settings id in token is draft AND user is logged in as admin create set_settings key if valid
        // if user is not logged in get default active settings
        $service = new SsoAzureService();
        $uac = $service->assertStateCodeAndGetUac($state, $code, $this->User->ip(), $this->User->userAgent());

        // Create token for next step, e.g. get keys
        $token = $service->createSsoAuthTokenToGetKey($uac, $service->getSettings()->id);

        $this->response = $this->getResponse()->withCookie($service->clearStateCookie());
        $this->redirect(Router::url('/sso/login/success?token=') . $token->token);
    }
}
