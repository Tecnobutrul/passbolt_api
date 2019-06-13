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
        // Remove organization name from urls
        // So that regular passbolt url works
        $path = $request->getUri()->getPath();
        $path = (explode('/', $path, 3));
        if (!count($path)) {
            throw new Exception('The organization is not defined in request');
        } else {
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

    static function isMultiTenant()
    {
        $argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];

        $ignoreShells = [
            'multi_tenant',
            'migrations',
            'EmailQueue.sender',
            'EmailQueue.preview'
        ];

        $ignoreRoutes = [
            '/\/multi_tenant\/organizations/',
        ];

        $executeShell = isset($argv[1]) && !in_array($argv[1], $ignoreShells);
        $executeRoute = isset($_SERVER['REQUEST_URI']);
        if (isset($_SERVER['REQUEST_URI'])) {
            $matches = array_filter($ignoreRoutes, function($regexp) {
                $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                return (bool)preg_match("$regexp", $path);
            });
            $executeRoute = (count($matches) === 0);
        }

        // Organization will be ignored if set to 0.
        $ignoreMainOrganization = defined('PASSBOLT_ORG') && PASSBOLT_ORG === 0;
        $isMultiTenant = !$ignoreMainOrganization && ($executeShell || $executeRoute);

        return $isMultiTenant;
    }
}
