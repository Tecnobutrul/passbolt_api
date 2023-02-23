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
 * @since         3.11.0
 */

namespace Passbolt\SsoRecover\Service;

use App\Utility\ExtendedUserAccessControl;
use Cake\Http\Exception\BadRequestException;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Passbolt\Sso\Model\Entity\SsoAuthenticationToken;
use Passbolt\Sso\Model\Entity\SsoState;
use Passbolt\Sso\Service\Sso\AbstractSsoService;
use Passbolt\Sso\Service\SsoAuthenticationTokens\SsoAuthenticationTokenSetService;
use Passbolt\Sso\Service\SsoStates\SsoStatesAssertService;

class SsoRecoverAssertAssertService
{
    use LocatorAwareTrait;

    /**
     * Fetches resource owner details from state & code and builds UAC object.
     *
     * @param \Passbolt\Sso\Service\Sso\AbstractSsoService $ssoService SSO service.
     * @param \Passbolt\Sso\Model\Entity\SsoState $ssoState SSO state entity.
     * @param string $code Code.
     * @param string $ip IP.
     * @param string $userAgent User agent.
     * @return \Passbolt\Sso\Model\Entity\SsoAuthenticationToken
     */
    public function assertStateCodeAndGetAuthToken(
        AbstractSsoService $ssoService,
        SsoState $ssoState,
        string $code,
        string $ip,
        string $userAgent
    ): SsoAuthenticationToken {
        try {
            $resourceOwner = $ssoService->getResourceOwner($code);

            $ssoService->assertResourceOwnerAgainstSsoState($resourceOwner, $ssoState);
        } catch (\Exception $e) {
            $msg = 'There was an error while retrieving resource owner. ';
            $msg .= "Message: {$e->getMessage()}, State ID: {$ssoState->state}";

            Log::error($msg);

            throw $e;
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');
        /** @var \App\Model\Entity\User|null $user */
        $user = $usersTable->findByUsername($resourceOwner->getEmail())->first();

        if ($user === null) {
            throw new BadRequestException(__('The user does not exist or has been deleted.'));
        }

        $uac = new ExtendedUserAccessControl($user->role->name, $user->id, $user->username, $ip, $userAgent);

        (new SsoStatesAssertService())->assertAndConsumeWithoutUser($ssoState, $ssoService->getSettings()->id, $uac);

        return (new SsoAuthenticationTokenSetService())->createOrFail(
            $uac,
            SsoState::TYPE_SSO_RECOVER,
            $ssoService->getSettings()->id
        );
    }

    /**
     * Returns success URL to redirect.
     *
     * @param string $token Token to set in query parameter
     * @return string
     */
    public function getSuccessUrl(string $token): string
    {
        return Router::url('/sso/recover/azure/success?token=') . $token;
    }
}
