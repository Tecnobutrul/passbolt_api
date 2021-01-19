<?php
declare(strict_types=1);

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
namespace Passbolt\MultiTenant\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MultitenantRoutingMiddleware
{
    /**
     * @inheritDoc
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $next)
    {
        // Remove organization name from urls
        // So that regular passbolt url works
        $isCli = PHP_SAPI === 'cli';
        if (!$isCli) {
            $path = $request->getUri()->getPath();
            $arr = explode('/', $path, 3);
            $newpath = '/';
            if (isset($arr[2])) {
                $newpath = '/' . $arr[2];
            }

            $request = $request->withUri($request->getUri()->withPath($newpath));
        }

        // Calling $next() delegates control to the *next* middleware
        // In your application's queue.
        $response = $next($request, $response);

        return $response;
    }
}
