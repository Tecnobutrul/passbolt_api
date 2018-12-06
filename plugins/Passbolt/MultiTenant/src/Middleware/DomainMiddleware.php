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
namespace Passbolt\MultiTenant\Middleware;

use Cake\Core\Exception\Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DomainMiddleware
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $next)
    {
        // Get organization name and rewrite urls
        $path = $request->getUri()->getPath();
        $path = (explode('/', $path, 3));
        if (!count($path)) {
            throw new Exception('The organization is not defined in request');
        } else {
            // Define passbolt domain
            $org = $path[1];
            // Redefine new path
            if (isset($path[2])) {
                $newpath = '/' . $path[2];
            } else {
                $newpath = '/';
            }
        }
        $request = $request->withUri($request->getUri()->withPath($newpath));

        // Calling $next() delegates control to the *next* middleware
        // In your application's queue.
        $response = $next($request, $response);

        return $response;
    }
}
