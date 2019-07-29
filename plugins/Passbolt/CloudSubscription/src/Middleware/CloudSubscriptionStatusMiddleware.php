<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace Passbolt\CloudSubscription\Middleware;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CloudSubscriptionStatusMiddleware
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $next)
    {
        // Re-route if status expired
        if ($this->requireRedirect($request)) {
            return $response
                ->withStatus(302)
                ->withLocation($this->getRedirectUrl($request));
        }

        // Calling $next() delegates control to the *next* middleware
        // In your application's queue.
        $response = $next($request, $response);

        return $response;
    }

    protected function requireRedirect($request)
    {


        // Do not redirect on mfa setup or check page
        // same goes for authentication pages
        $whitelistedPaths = [
            '/subscription/disabled',
            '/subscription/archived'
        ];
        foreach ($whitelistedPaths as $path) {
            if (substr($request->getUri()->getPath(), 0, strlen($path)) === $path) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ServerRequest $request request
     * @return string
     */
    protected function getRedirectUrl(ServerRequest $request)
    {
        $url = '/subscription/disabled';
        if ($request->is('json')) {
            $url .= '.json';
        }

        return Router::url($url, true);
    }
}
