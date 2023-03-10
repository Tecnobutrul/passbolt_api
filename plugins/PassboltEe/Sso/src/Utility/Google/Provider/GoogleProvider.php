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
 * @since         4.0.0
 */

namespace Passbolt\Sso\Utility\Google\Provider;

use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotImplementedException;
use Cake\Validation\Validation;
use Firebase\JWT\Key;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Passbolt\Sso\Error\Exception\GoogleException;
use Passbolt\Sso\Utility\Azure\Grant\JwtBearer;
use Passbolt\Sso\Utility\Azure\ResourceOwner\AzureResourceOwner;
use Passbolt\Sso\Utility\OpenId\IdToken;
use Psr\Http\Message\ResponseInterface;

class GoogleProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string Default base url for OpenID.
     */
    public $openIdBaseUri = 'https://accounts.google.com';

    /**
     * @var array|null
     */
    protected $openIdConfiguration = null;

    /**
     * @inheritDoc
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        // TODO: Not sure what & why this is?
        $this->grantFactory->setGrant('jwt_bearer', new JwtBearer());
    }

    /*
     * ABSTRACT METHODS
     * See. AbstractProvider
     */

    /**
     * @inheritDoc
     */
    public function getBaseAuthorizationUrl(): string
    {
        $openIdConfiguration = $this->getOpenIdConfiguration();

        return $openIdConfiguration['authorization_endpoint'];
    }

    /**
     * @inheritDoc
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        $openIdConfiguration = $this->getOpenIdConfiguration();

        return $openIdConfiguration['token_endpoint'];
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwner(AccessToken $token): ResourceOwnerInterface
    {
        // We get resource owner information from id_token only
        // We could fall back calling user info API user access_token but we rather not
        if ($token instanceof IdToken) {
            $data = $token->getIdTokenClaims();

            return $this->createResourceOwner($data, $token);
        }

        throw new InternalErrorException('AccessToken should implement IdToken interface.');
    }

    /**
     * @return string
     */
    public function getOpenIdConfigurationUri(): string
    {
        return $this->openIdBaseUri . '/.well-known/openid-configuration';
    }

    /**
     * @return array
     */
    protected function getOpenIdConfiguration(): array
    {
        if (isset($this->openIdConfiguration)) {
            return $this->openIdConfiguration;
        }

        $factory = $this->getRequestFactory();
        $request = $factory->getRequestWithOptions(
            'get',
            $this->getOpenIdConfigurationUri(),
            []
        );

        try {
            $response = $this->getParsedResponse($request);
        } catch (\Exception $exception) {
            throw new InternalErrorException($exception->getMessage(), 500, $exception);
        }

        $this->validateOpenIdConfiguration($response);
        $this->openIdConfiguration = $response;

        return $this->openIdConfiguration;
    }

    /**
     * Check the endpoints info we expect to use later are present
     *
     * @param mixed $response from .well-known
     * @return void
     */
    public function validateOpenIdConfiguration($response): void
    {
        if (!is_array($response)) {
            throw new InternalErrorException('Invalid response.');
        }
        if (!isset($response['jwks_uri'])) {
            throw new InternalErrorException('Invalid response. Missing JWKS URI');
        }
        if (!isset($response['authorization_endpoint'])) {
            throw new InternalErrorException('Invalid response. Missing authorization endpoint.');
        }
        if (!isset($response['token_endpoint'])) {
            throw new InternalErrorException('Invalid response. Missing token endpoint.');
        }
        if (!Validation::url($response['jwks_uri'])) {
            throw new InternalErrorException('Invalid response. Invalid JWKS URI');
        }
        if (!Validation::url($response['authorization_endpoint'])) {
            throw new InternalErrorException('Invalid response. Invalid authorization endpoint.');
        }
        if (!Validation::url($response['token_endpoint'])) {
            throw new InternalErrorException('Invalid response. Invalid token endpoint.');
        }
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        // Not needed, we will get the resource owner information from JWT token claims
        // And not the userinfo_endpoint
        throw new NotImplementedException();
    }

    /**
     * Get JWT verification keys from Google.
     *
     * @return array
     */
    public function getJwtVerificationKeys()
    {
        $openIdConfiguration = $this->getOpenIdConfiguration();
        $keysUri = $openIdConfiguration['jwks_uri'];

        $factory = $this->getRequestFactory();
        /**
         * The response should be cached in production application.
         *
         * @link https://developers.google.com/identity/openid-connect/openid-connect#validatinganidtoken
         */
        $request = $factory->getRequestWithOptions('get', $keysUri, []);

        try {
            $response = $this->getParsedResponse($request);
        } catch (\Throwable $exception) {
            throw new InternalErrorException(__('Cannot parse JWKS endpoint response.'), 500, $exception);
        }

        if (!is_array($response) || !isset($response['keys'])) {
            throw new InternalErrorException(__('Invalid JWKS endpoint response. Keys missing.'));
        }

        return $this->parseJwksKeys($response['keys']);
    }

    /**
     * @param array $responseKeys keys from Jwks endpoint
     * @return array of openssl compatible keys
     */
    public function parseJwksKeys(array $responseKeys): array
    {
        $keys = [];
        foreach ($responseKeys as $i => $keyinfo) {
            if (isset($keyinfo['x5c']) && is_array($keyinfo['x5c'])) {
                foreach ($keyinfo['x5c'] as $encodedkey) {
                    $cert =
                        '-----BEGIN CERTIFICATE-----' . PHP_EOL
                        . chunk_split($encodedkey, 64, PHP_EOL)
                        . '-----END CERTIFICATE-----' . PHP_EOL;

                    $cert_object = openssl_x509_read($cert);

                    if ($cert_object === false) {
                        throw new InternalErrorException(__('Failed to read certificate: {0}', $encodedkey));
                    }

                    $pkey_object = openssl_pkey_get_public($cert_object);

                    if ($pkey_object === false) {
                        $msg = __('Failed to read public key from certificate: {0}', $encodedkey);
                        throw new InternalErrorException($msg);
                    }

                    $pkey_array = openssl_pkey_get_details($pkey_object);

                    if ($pkey_array === false) {
                        $msg = __('Failed to public key properties from certificate: {0}', $encodedkey);
                        throw new InternalErrorException($msg);
                    }

                    $publicKey = $pkey_array['key'];

                    $keys[$keyinfo['kid']] = new Key($publicKey, 'RS256');
                }
            }
        }

        if (empty($keys)) {
            throw new InternalErrorException('Not JWT key defined for Azure service.');
        }

        return $keys;
    }

    /*
     * REDEFINED METHODS
     * See AbstractProvider
     */

    /**
     * @inheritDoc
     */
    protected function getDefaultScopes(): array
    {
        /** Note: "openid" MUST be the first scope in the list. */
        return ['openid', 'profile', 'email'];
    }

    /**
     * @inheritDoc
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * @inheritDoc
     */
    protected function createAccessToken(array $response, AbstractGrant $grant): AccessToken
    {
        return new IdToken($response, $this);
    }

    /**
     * @inheritDoc
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new AzureResourceOwner($response); // TODO: Change
    }

    /**
     * @inheritDoc
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (empty($data['error'])) {
            return;
        }

        if (is_string($data['error']) && isset($data['error_description']) && is_string($data['error_description'])) {
            throw new GoogleException($data['error'], $data['error_description']);
        } else {
            throw new IdentityProviderException(
                $response->getReasonPhrase(),
                $response->getStatusCode(),
                (string)$response->getBody()
            );
        }
    }
}
