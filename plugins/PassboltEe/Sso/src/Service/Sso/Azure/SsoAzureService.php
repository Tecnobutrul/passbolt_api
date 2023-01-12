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

namespace Passbolt\Sso\Service\Sso\Azure;

use App\Utility\ExtendedUserAccessControl;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Routing\Router;
use League\OAuth2\Client\Provider\AbstractProvider;
use Passbolt\Sso\Model\Dto\SsoSettingsAzureDataDto;
use Passbolt\Sso\Model\Dto\SsoSettingsDto;
use Passbolt\Sso\Model\Entity\SsoSetting;
use Passbolt\Sso\Service\Sso\AbstractSsoService;
use Passbolt\Sso\Service\SsoSettings\SsoSettingsGetService;
use Passbolt\Sso\Utility\Azure\Provider\AzureProvider;

class SsoAzureService extends AbstractSsoService
{
    // ABSTRACT CLASS PUBLIC FUNCTIONS DEFINITION

    /**
     * Get authorization URL from the provider; this returns the
     * urlAuthorize option and generates and applies any necessary parameters
     *
     * @param \App\Utility\ExtendedUserAccessControl $uac user access control
     * @return string
     */
    public function getAuthorizationUrl(ExtendedUserAccessControl $uac): string
    {
        $options = [
            'login_hint' => $uac->getUsername(),
            'response_type' => 'code',
        ];

        if (Configure::read('passbolt.plugins.sso.security.prompt')) {
            $options['prompt'] = 'login';
        }

        return $this->provider->getAuthorizationUrl($options);
    }

    // ABSTRACT CLASS PROTECTED FUNCTIONS DEFINITION

    /**
     * @param \Passbolt\Sso\Model\Dto\SsoSettingsDto $settings setting
     * @return \League\OAuth2\Client\Provider\AbstractProvider
     */
    protected function getOAuthProvider(SsoSettingsDto $settings): AbstractProvider
    {
        /** @var \Passbolt\Sso\Model\Dto\SsoSettingsAzureDataDto $data */
        $data = $settings->data;

        return new AzureProvider([
            'clientId' => $data->client_id,
            'clientSecret' => $data->client_secret,
            'redirectUri' => Router::url('/sso/azure/redirect', true),
            'tenant' => $data->tenant_id,
            'urlLogin' => $data->url ?? null,
        ]);
    }

    /**
     * @return \Passbolt\Sso\Model\Dto\SsoSettingsDto
     */
    protected function assertAndGetSsoSettings(): SsoSettingsDto
    {
        try {
            $ssoSettings = (new SsoSettingsGetService())->getActiveOrFail(true);
            if ($ssoSettings->provider !== SsoSetting::PROVIDER_AZURE) {
                throw new BadRequestException('Invalid provider. Expected Azure as provider.');
            }
            if (!($ssoSettings->data instanceof SsoSettingsAzureDataDto)) {
                throw new BadRequestException('Invalid provider data. Expected Azure settings.');
            }
        } catch (\Exception $exception) {
            throw new BadRequestException(__('No valid SSO settings found.'), 400, $exception);
        }

        return $ssoSettings;
    }

    // HELPERS
}
