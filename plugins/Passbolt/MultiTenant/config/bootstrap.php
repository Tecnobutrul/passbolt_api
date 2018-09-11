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
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Event\EventManager;
use Passbolt\MultiTenant\Middleware\DomainMiddleware;

// Get cli details.
$isCli = PHP_SAPI === 'cli';
$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];

// Load default plugin configuration file while keeping plugin conf defined in passbolt.php.
$original = Configure::read('passbolt.multiTenant');
Configure::load('Passbolt/MultiTenant.config', 'default', true);
$defaults = Configure::read('passbolt.multiTenant');
$merged = array_merge($defaults, $original);
Configure::write('passbolt.multiTenant', $merged);

// Extract organization from cli parameter, or url.
if ($isCli) {
    foreach ($argv as $i => $value) {
        preg_match('/--org=(.+)/', $value, $match);
        if (isset($match[1])) {
            define('PASSBOLT_ORG', $match[1]);
            define('CACHE_ORG', CACHE . PASSBOLT_ORG . DS);
            break;
        }
    }
} else {
    $match = (explode('/', $_SERVER['REQUEST_URI'], 3));
    if (isset($match[1]) && !empty($match[1])) {
        define('PASSBOLT_ORG', $match[1]);
        define('CACHE_ORG', CACHE . PASSBOLT_ORG . DS);
    } else {
        // Redirect to main website if the root is requested.
        $redirectUrl = Configure::read('passbolt.multiTenant.rootRedirectUrl');
        header('Location: ' . $redirectUrl, true, 301);
        die();
    }
}

// Check if multitenant settings apply.
$ignoreShells = [
    'multi_tenant',
    'migrations', // TODO: find a way to make the migrations shell understand the org command
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
    $executeRoute = count($matches) == 0;
}


// Organization will be ignored if set to 0.
$ignoreMainOrganization = defined('PASSBOLT_ORG') && PASSBOLT_ORG === 0;
$isMultiTenant = !$ignoreMainOrganization && ($executeShell || $executeRoute);

if ($isMultiTenant) {
    if ($isCli && (!defined('PASSBOLT_ORG'))) {
        trigger_error('--org parameter should be provided', E_USER_ERROR);
        exit;
    }

    // Load Middleware and put it first in the queue.
    EventManager::instance()->on(
        'Server.buildMiddleware',
        function ($event, $middlewareQueue) {
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
