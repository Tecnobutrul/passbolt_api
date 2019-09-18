<?php
/**
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\CloudSubscription\Middleware;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Routing\Router;
use Passbolt\CloudSubscription\Service\CloudSubscriptionSettings;
use PDOException;

class CloudSubscriptionStatusMiddleware
{
    private $redirectUrl;

    /** @var ServerRequest $request */
    private $request;

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequest $request, Response $response, $next)
    {
        $this->request = $request;
        if ($this->requireRedirect()) {
            return $response
                ->withStatus(302)
                ->withLocation($this->redirectUrl);
        }
        $response = $next($this->request, $response);

        return $response;
    }

    /**
     * @return bool true if redirect is required
     */
    protected function requireRedirect()
    {
        if ($this->isPathStartingWith('/multitenant/admin')) {
            return false;
        }

        try {
            $subscription = CloudSubscriptionSettings::get();
            $subscription->updateStatusIfExpired();
            $pdoError = false;
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            $subscription = false;
            $pdoError = false;
        } catch (PDOException $exception) {
            Log::error($exception->getMessage());
            $subscription = false;
            $pdoError = true;
        }

        // Handle case where DB is not available
        // Could be an error or it could mean the org does not exist
        if ($pdoError) {
            // if subscription is present display an internal error
            if ($this->isPathStartingWith('/subscription/notfound')) {
                return false;
            }
            $this->redirectUrl = $this->getRedirectUrl('/subscription/notfound');

            return true;
        }

        // Handle the case where PDO is working but no subscription is found
        if ($subscription === false) {
            if (Configure::read('debug')) {
                $msg = sprintf('Subscription missing for %s. Ignoring subscription check.', PASSBOLT_ORG);
                Log::error($msg);
            }

            return false;
        }

        // Handle case where DB is not present but schedule for deletion
        if ($subscription->isDeleted()) {
            // if subscription is present display an internal error
            if ($this->isPathStartingWith('/subscription/notfound')) {
                return false;
            }
            $this->redirectUrl = $this->getRedirectUrl('/subscription/notfound');

            return true;
        }

        // Prevent accessing the /notfound page directly
        if ($this->isPathStartingWith('/subscription/notfound')) {
            $this->redirectUrl = $this->getRedirectUrl('/');

            return true;
        }

        // Handle case where subscription is not found or subscription is expired
        if ($subscription->isDisabled()) {
            if ($this->isPathStartingWith('/subscription/disabled')) {
                return false;
            }
            $this->redirectUrl = $this->getRedirectUrl('/subscription/disabled');

            return true;
        }

        // prevent accessing /disabled directly
        if ($this->isPathStartingWith('/subscription/disabled')) {
            $this->redirectUrl = $this->getRedirectUrl('/');

            return true;
        }

        return false;
    }

    /**
     * Return true if request path starts with given string
     *
     * @param string $path path to compare
     * @return bool
     */
    protected function isPathStartingWith(string $path)
    {
        return (substr($this->request->getUri()->getPath(), 0, strlen($path)) === $path);
    }

    /**
     * @param string $url url
     * @return string
     */
    protected function getRedirectUrl(string $url)
    {
        if ($this->request->is('json')) {
            $url .= '.json';
        }

        return Router::url($url, true);
    }
}
