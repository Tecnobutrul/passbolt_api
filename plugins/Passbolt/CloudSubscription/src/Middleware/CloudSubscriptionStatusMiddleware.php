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
 * @since         2.11.0
 */
namespace Passbolt\CloudSubscription\Middleware;

use Cake\Core\Exception\Exception;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Passbolt\CloudSubscription\Utility\CloudSubscriptionSettings;
use PDOException;

class CloudSubscriptionStatusMiddleware
{
    private $redirectUrl;

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequest $request, Response $response, $next)
    {
        // Re-route if status expired
        if ($this->requireRedirect($request)) {
            return $response
                ->withStatus(302)
                ->withLocation($this->redirectUrl);
        }

        // Calling $next() delegates control to the *next* middleware
        // In your application's queue.
        $response = $next($request, $response);

        return $response;
    }

    protected function requireRedirect(ServerRequest $request)
    {
        try {
            $subscription = CloudSubscriptionSettings::get();
            $pdoError = false;
        } catch (Exception $exception) {
            $subscription = false;
            $pdoError = false;
        } catch (PDOException $exception) {
            $subscription = false;
            $pdoError = true;
        }

        // Handle case where DB is not available
        // Could be an error or it could mean the org does not exist
        if ($pdoError) {
            // TODO add a manual check to the catalog to check subscription
            // if subscription is present display an internal error
            if ($this->isPathMatching($request, '/subscription/notfound')) {
                return false;
            }
            $this->redirectUrl = $this->getRedirectUrl($request, '/subscription/notfound');
            return true;
        }
        // Prevent accessing the /notfound page directly
        if ($this->isPathMatching($request, '/subscription/notfound')) {
            $this->redirectUrl = $this->getRedirectUrl($request, '/');
            return true;
        }

        // Handle case where subscription is not found or subscription is expired
        if ($subscription === false || $subscription->isExpired()) {
            if ($this->isPathMatching($request, '/subscription/disabled')) {
                return false;
            }
            $this->redirectUrl = $this->getRedirectUrl($request, '/subscription/disabled');
            return true;
        }
        // prevent accessing /disabled directly
        if ($this->isPathMatching($request, '/subscription/disabled')) {
            $this->redirectUrl = $this->getRedirectUrl($request, '/');
            return true;
        }

        return false;
    }

    /**
     * @param ServerRequest $request
     * @return bool
     */
    protected function isPathMatching(ServerRequest $request, $path) {
        return (substr($request->getUri()->getPath(), 0, strlen($path)) === $path);
    }

    /**
     * @param ServerRequest $request request
     * @param string $url
     * @return string
     */
    protected function getRedirectUrl(ServerRequest $request, string $url)
    {
        if ($request->is('json')) {
            $url .= '.json';
        }

        return Router::url($url, true);
    }
}
