<?php
declare(strict_types=1);

/**
 * MIT License
 *
 * Copyright (c) 2022 Passbolt SA (https://www.passbolt.com)
 * Copyright (c) 2016 TheNetw.org
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of
 * the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @link          https://github.com/TheNetworg/oauth2-azure/blob/master/src/Token/AccessToken.php
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         3.9.0
 */
namespace Passbolt\Sso\Utility\OpenId;

use App\Model\Validation\EmailValidationRule;
use Cake\Http\Exception\BadRequestException;
use Firebase\JWT\JWT;
use League\OAuth2\Client\Token\AccessToken;
use Passbolt\Sso\Utility\Azure\Provider\AzureProvider;

/**
 * Extend BaseAccessToken to include id_token support
 * id_token is OIDC specific (e.g. on top of OAuth2)
 */
class IdToken extends AccessToken
{
    /**
     * @var \Passbolt\Sso\Utility\Azure\Provider\AzureProvider $provider provider
     */
    protected $provider;

    /**
     * @var string
     */
    protected $idToken;

    /**
     * @var array
     */
    protected $idTokenClaims;

    /**
     * @param array $options such as access_token, refresh_token and id_token
     * @param \Passbolt\Sso\Utility\Azure\Provider\AzureProvider $provider provider
     * @throws \Cake\Http\Exception\InternalErrorException if keys to verify JWT cannot be fetched or validated
     * @throws \Cake\Http\Exception\BadRequestException if JWT doesn't validate
     */
    public function __construct(array $options, AzureProvider $provider)
    {
        parent::__construct($options);
        $this->provider = $provider;

        if (empty($options['id_token']) || !is_string($options['id_token'])) {
            throw new BadRequestException(__('Azure JWT token is missing.'));
        }
        $this->idToken = $options['id_token'];
        unset($this->values['id_token']);

        $keys = $provider->getJwtVerificationKeys();
        try {
            $tokenClaims = (array)JWT::decode($this->idToken, $keys);
        } catch (\Exception $exception) {
            throw new BadRequestException(__('Unable to decode Azure JWT token.'), 400, $exception);
        }

        $this->assertTokenClaims($tokenClaims);

        $this->idTokenClaims = $tokenClaims;
    }

    /**
     * Validate the access token claims from an access token you received in your application.
     * Note: nbf and exp claims are validated in JWT::decode
     *
     * @param array $tokenClaims The token claims from an access token you received in the authorization header.
     * @throws \Cake\Http\Exception\BadRequestException if any of the claim is invalid
     * @return void
     */
    public function assertTokenClaims(array $tokenClaims): void
    {
        if (empty($tokenClaims)) {
            throw new BadRequestException('No claims');
        }

        if (
            !isset($tokenClaims['aud']) || !is_string($tokenClaims['aud']) ||
            $this->provider->getClientId() != $tokenClaims['aud']
        ) {
            throw new BadRequestException('The aud (client id) parameter is invalid');
        }

        if (
            !isset($tokenClaims['tid']) || !is_string($tokenClaims['tid']) ||
            $this->provider->getTenant() != $tokenClaims['tid']
        ) {
            throw new BadRequestException('The tid (tenant id) parameter is invalid.');
        }

        if (
            !isset($tokenClaims['ver']) || !is_string($tokenClaims['ver']) ||
            $tokenClaims['ver'] != AzureProvider::ENDPOINT_VERSION_2_0
        ) {
            throw new BadRequestException('The ver (version) parameter is invalid.');
        }

        if (
            !isset($tokenClaims['iss']) || !is_string($tokenClaims['iss']) ||
            $tokenClaims['iss'] != $this->provider->getOpenIdBaseUri()
        ) {
            throw new BadRequestException('The iss (issuer) parameter is invalid.');
        }

        if (!isset($tokenClaims['email']) || !EmailValidationRule::check($tokenClaims['email'])) {
            throw new BadRequestException('The email claim is not found or invalid.');
        }
    }

    /**
     * @return string id_token
     */
    public function getIdToken(): string
    {
        return $this->idToken;
    }

    /**
     * @return array claims from JWT::decode(id_token)
     */
    public function getIdTokenClaims(): array
    {
        return $this->idTokenClaims;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $parameters = parent::jsonSerialize();

        if ($this->idToken) {
            $parameters['id_token'] = $this->idToken;
        }

        return $parameters;
    }
}
