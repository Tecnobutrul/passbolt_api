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
use Cake\Event\EventManager;
use Passbolt\MultiOrg\Middleware\DomainMiddleware;
use Cake\Core\Configure\Engine\PhpConfig;

// Get cli details.
$isCli = PHP_SAPI === 'cli';
$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];

Configure::load('Passbolt/MultiOrg.config', 'default', true);

// Load Middleware and put it first in the queue.
EventManager::instance()->on(
    'Server.buildMiddleware',
    function ($event, $middlewareQueue) {
        $middlewareQueue->prepend(DomainMiddleware::class);
    });

// Define CONFIG_ORG based on the plugin configuration.
define('CONFIG_ORG', Configure::read('passbolt.multiOrg.configDir'));

// Custom init step for Passbolt Multi Tenant
if ($isCli) {
    foreach ($argv as $i => $value) {
        preg_match('/--org=(.+)/', $value, $match);
        if (isset($match[1])) {
            define('PASSBOLT_ORG', $match[1]);
            break;
        }
    }
} else {
    $match = (explode('/', $_SERVER['REQUEST_URI'],3));
    if (isset($match[1])) {
        define('PASSBOLT_ORG', $match[1]);
    }
}

if (!defined('PASSBOLT_ORG') || !file_exists( CONFIG_ORG . DS . PASSBOLT_ORG . DS . 'passbolt.php')) {
    // If we are using the organization shell, we don't need the organization name to be provided.
    // We operate at a general level.
    if (!isset($argv[1]) || $argv[1] !== 'organization') {
        // If the org is not provided for any other shells, it fails.
        echo 'This organization is missing.';
        trigger_error('This organization is missing.', E_USER_ERROR);
        exit;
    }
}

if (defined('PASSBOLT_ORG')) {
    Configure::config('org', new PhpConfig(CONFIG_ORG . DS));
    Configure::load(PASSBOLT_ORG . DS . 'passbolt', 'org', true);
}