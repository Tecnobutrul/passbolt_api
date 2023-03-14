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

namespace Passbolt\Sso\Test\TestCase\Utility\Google\OpenId;

use Cake\Http\Exception\BadRequestException;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Passbolt\Sso\Test\Lib\GoogleProviderTestTrait;
use Passbolt\Sso\Utility\Google\OpenId\GoogleIdToken;

/**
 * @see \Passbolt\Sso\Utility\Google\OpenId\GoogleIdToken
 */
class GoogleIdTokenTest extends TestCase
{
    use GoogleProviderTestTrait;

    public function testSsoGoogleIdToken_ErrorMissingOptions(): void
    {
        $provider = $this->getDummyGoogleProvider();
        $this->expectException(InvalidArgumentException::class);

        $options = [];
        new GoogleIdToken($options, $provider);
    }

    public function testSsoGoogleIdToken_ErrorMissinIdToken(): void
    {
        $provider = $this->getDummyGoogleProvider();
        $this->expectException(BadRequestException::class);

        $options = [
            'access_token' => 'test',
            'exp' => 0,
        ];
        new GoogleIdToken($options, $provider);
    }

    public function testSsoGoogleIdToken_ErrorInvalidIdToken(): void
    {
        $provider = $this->getDummyGoogleProvider();
        $this->expectException(BadRequestException::class);

        $options = [
            'access_token' => 'test',
            'exp' => 0,
            'id_token' => [],
        ];
        new GoogleIdToken($options, $provider);
    }
}
