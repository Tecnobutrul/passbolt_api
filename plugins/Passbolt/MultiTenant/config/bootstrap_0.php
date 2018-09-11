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

/**
 * This bootstrap is the bootstrap zero.
 * It is meant to be loaded at the top of the main application bootstrap before anything else.
 * It is responsible of loading the base variables of the multitenant configuration.
 */

// Get cli details.
$isCli = PHP_SAPI === 'cli';
$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];

if ($isCli) {
    foreach ($argv as $i => $value) {
        preg_match('/--org=(.+)/', $value, $match);
        if (isset($match[1])) {
            define('PASSBOLT_ORG', $match[1]);
            break;
        }
    }
} else {
    $match = (explode('/', $_SERVER['REQUEST_URI'], 3));
    if (isset($match[1]) && !empty($match[1])) {
        define('PASSBOLT_ORG', $match[1]);
    }
}

if (defined('PASSBOLT_ORG')) {
    // Load configuration for the cache.
    define('CACHE_ORG', CACHE . PASSBOLT_ORG . DS);
}