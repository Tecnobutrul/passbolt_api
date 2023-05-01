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

namespace Passbolt\Sso\Model\Dto;

use Cake\Chronos\ChronosInterface;

/**
 * AzureDataDto Data Transfer Object
 */
class SsoSettingsAzureDataDto implements SsoSettingsDataDtoInterface
{
    /**
     * @var string $url
     */
    public $url;

    /**
     * @var string $client_id uuid
     */
    public $client_id;

    /**
     * @var string $client_secret
     */
    public $client_secret;

    /**
     * @var string|\Cake\Chronos\ChronosInterface
     */
    public $client_secret_expiry;

    /**
     * @var string $tenant_id
     */
    public $tenant_id;

    /**
     * @var string
     */
    public $prompt;

    /**
     * @param array $data with
     *  - url string
     *  - client_id string uuid
     *  - tenant_id string uuid
     *  - client_secret string
     *  - client_secret_expiry string|datetime
     */
    public function __construct(array $data)
    {
        $this->url = $data['url'] ?? '';
        $this->client_id = $data['client_id'] ?? '';
        $this->tenant_id = $data['tenant_id'] ?? '';
        $this->client_secret = $data['client_secret'] ?? '';
        $this->client_secret_expiry = $data['client_secret_expiry'] ?? '';
        $this->prompt = $data['prompt'] ?? '';
    }

    /**
     * @return array
     *  - url string
     *  - client_id string uuid
     *  - tenant_id string uuid
     *  - client_secret string
     *  - client_secret_expiry string
     */
    public function toArray(): array
    {
        $result = [
            'url' => $this->url,
            'client_id' => $this->client_id,
            'tenant_id' => $this->tenant_id,
            'client_secret' => $this->client_secret,
            'client_secret_expiry' => $this->client_secret_expiry,
            'prompt' => $this->prompt,
        ];

        // Serialize date if it's not already a string
        if ($result['client_secret_expiry'] instanceof ChronosInterface) {
            $result['client_secret_expiry'] = $this->client_secret_expiry->toDateTimeString();
        }

        return $result;
    }
}
