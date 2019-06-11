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
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Event\EventManager;
use Passbolt\MultiTenant\Middleware\DomainMiddleware;

// Extract organization from cli parameter, or url.
if (!$isCli && !defined('PASSBOLT_ORG')) {
    // Redirect to main website if the root is requested.
    $redirectUrl = Configure::read('passbolt.multiTenant.rootRedirectUrl');
    header('Location: ' . $redirectUrl, true, 301);
    die();
}

// Check if multitenant settings apply.
if (DomainMiddleware::isMultiTenant()) {

    // Load Middleware and put it first in the queue.
    EventManager::instance()->on(
        'Server.buildMiddleware',
        function (\Cake\Event\Event $event, \Cake\Http\MiddlewareQueue $middlewareQueue) {
            $middlewareQueue->prepend(DomainMiddleware::class);
        }
    );

    // Define CONFIG_ORG based on the plugin configuration.
    define('CONFIG_ORG', Configure::read('passbolt.multiTenant.configDir'));

    if (!defined('PASSBOLT_ORG') || !file_exists(CONFIG_ORG . DS . PASSBOLT_ORG . DS . 'passbolt.php')) {
        echo 'This organization is missing.';
        trigger_error('This organization is missing.', E_USER_ERROR);
        exit;
    }

    if (defined('PASSBOLT_ORG')) {
        Configure::config('org', new PhpConfig(CONFIG_ORG . DS));
        Configure::load(PASSBOLT_ORG . DS . 'passbolt', 'org', true);
    }
}
