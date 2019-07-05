<?php
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
 * @since         2.0.0
 */
namespace App;

use App\Middleware\CsrfProtectionMiddleware;
use App\Middleware\GpgAuthHeadersMiddleware;
use App\Utility\ImageStorage\GoogleCloudStorageListener;
use Burzum\FileStorage\Storage\Listener\ImageProcessingListener;
use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManager;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\SecurityHeadersMiddleware;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Passbolt\MultiTenant\Middleware\MultitenantRoutingMiddleware;

class Application extends BaseApplication
{
    /**
     * Setup the PSR-7 middleware passbolt application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware.
     */
    public function middleware($middleware)
    {
        // Multitenant route middleware.
        $middleware->prepend(MultitenantRoutingMiddleware::class);

        /*
         * Default Middlewares
         * - Catch any exceptions in the lower layers, and make an error page/response
         * - Handle plugin/theme assets like CakePHP normally does.
         * - Apply routing middleware
         * - Apply GPG Auth headers
         * - Apply CSRF protection
         */
        $middleware
            ->add(new ErrorHandlerMiddleware(null, Configure::read('Error')))
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime')
            ]))
            ->add(new RoutingMiddleware($this))
            ->add(GpgAuthHeadersMiddleware::class)
            ->add(new CsrfProtectionMiddleware());

        /*
         * Additional security headers
         * - Only allow assets to be loaded from the passbolt instance domain
         * - Only set the referrer header on requests to the same origin
         * - Don't allow framing the site
         * - Tell browser to block XSS attempts
         * - Don't allow
         * - Stick to the content type declared by the server
         */
        if (Configure::read('passbolt.security.setHeaders')) {
            $headers = new SecurityHeadersMiddleware();
            $headers
                ->setCrossDomainPolicy()
                ->setReferrerPolicy()
                ->setXFrameOptions()
                ->setXssProtection()
                ->noOpen()
                ->noSniff();

            $middleware->add($headers);
        }

        return $middleware;
    }

    /**
     * Load all the application configuration and bootstrap logic.
     *
     * Override this method to add additional bootstrap logic for your application.
     *
     * @return void
     */
    public function bootstrap()
    {
        parent::bootstrap();

        $this->addCorePlugins()
            ->addVendorPlugins()
            ->addPassboltPlugins();

        if (PHP_SAPI === 'cli') {
            $this->addCliPlugins();
        }
    }

    /**
     * Add core plugin
     * - DebugKit if debug mode is on
     * - Migration plugin
     *
     * @return $this
     */
    protected function addCorePlugins()
    {
        // Debug Kit should not be installed on a production system
        if (Configure::read('debug') && Configure::read('debugKit')) {
            $this->addPlugin('DebugKit', ['bootstrap' => true]);
        }
        // Enable Migration Plugin
        $this->addPlugin('Migrations');

        return $this;
    }

    /**
     * Add vendor plugins
     * - EmailQueue
     * - FileStorage
     *
     * @return $this
     */
    protected function addVendorPlugins()
    {
        $this->addPlugin('EmailQueue');
        $this->addPlugin('Burzum/FileStorage', ['bootstrap' => false, 'routes' => true]);
        $this->bootstrapFileStorage();
        $this->addPlugin('Burzum/Imagine');

        return $this;
    }

    /**
     *
     */
    protected function bootstrapFileStorage()
    {
        $listener = new GoogleCloudStorageListener();
        EventManager::instance()->on($listener);

        $listener = new ImageProcessingListener();
        EventManager::instance()->on($listener);
    }

    /**
     * Add passbolt plugins
     *
     * @return $this
     */
    protected function addPassboltPlugins()
    {
        $this->addPlugin('Passbolt/MultiTenant', ['bootstrap' => true, 'routes' => false]);
        if (env('PASSBOLT_PLUGINS_MULTITENANTADMIN_ENABLED', false)) {
            $this->addPlugin('Passbolt/MultiTenantAnalytics', ['bootstrap' => true, 'routes' => false]);
            $this->addPlugin('Passbolt/MultiTenantAdmin', ['bootstrap' => true, 'routes' => true]);
        }

        if (Configure::read('debug') && Configure::read('passbolt.selenium.active')) {
            $this->addPlugin('PassboltSeleniumApi', ['bootstrap' => true, 'routes' => true]);
            $this->addPlugin('PassboltTestData', ['bootstrap' => true, 'routes' => false]);
        }

        $this->addPlugin('Passbolt/Pro', ['bootstrap' => true, 'routes' => false]);

        // Add Common plugins.
        $this->addPlugin('Passbolt/AccountSettings', ['bootstrap' => true, 'routes' => true]);
        $this->addPlugin('Passbolt/Export', ['bootstrap' => true, 'routes' => false]);
        $this->addPlugin('Passbolt/Import', ['bootstrap' => true, 'routes' => true]);
        $this->addPlugin('Passbolt/RememberMe', ['bootstrap' => true, 'routes' => false]);
        $this->addPlugin('Passbolt/EmailNotificationSettings', ['bootstrap' => true, 'routes' => true ]);

        $mfaEnabled = Configure::read('passbolt.plugins.multiFactorAuthentication.enabled');
        if (!isset($mfaEnabled) || $mfaEnabled) {
            $this->addPlugin('Passbolt/MultiFactorAuthentication', ['bootstrap' => true, 'routes' => true]);
        }

        // Allow switching on / off tags plugin
        $tagsEnabled = Configure::read('passbolt.plugins.tags.enabled');
        if (!isset($tagsEnabled) || $tagsEnabled) {
            $this->addPlugin('Passbolt/Tags', ['bootstrap' => true, 'routes' => true]);
        }

        $logEnabled = Configure::read('passbolt.plugins.log.enabled');
        if (!isset($logEnabled) || $logEnabled) {
            $this->addPlugin('Passbolt/Log', ['bootstrap' => true, 'routes' => false]);
            $this->addPlugin('Passbolt/AuditLog', ['bootstrap' => true, 'routes' => true]);
        }

        return $this;
    }

    /**
     * Add plugins relevant in CLI mode
     * - Bake
     * - Migrations
     *
     * @return $this
     */
    protected function addCliPlugins()
    {
        try {
            Application::addPlugin('Bake');
        } catch (MissingPluginException $e) {
            // Do not halt if the plugin is missing
        }

        return $this;
    }
}
