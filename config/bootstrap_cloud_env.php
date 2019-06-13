<?php
/*
 * Passbolt Cloud Bootstrap specifics constants
 */
$isCli = PHP_SAPI === 'cli';
$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
if(!defined('PASSBOLT_ORG')) {
    if ($isCli) {
        // In command line mode we expect the org to be passed using --org parameter
        foreach ($argv as $i => $value) {
            preg_match('/--org=(.+)/', $value, $match);
            if (isset($match[1])) {
                define('PASSBOLT_ORG', $match[1]);
                break;
            }
        }
    } else {
        // In HTTP request we expect the org to be the first part of the url
        $match = (explode('/', $_SERVER['REQUEST_URI'], 3));
        if (isset($match[1]) && !empty($match[1])) {
            define('PASSBOLT_ORG', $match[1]);
        }
    }
}

// Set the cache prefix to use for this org
if (defined('PASSBOLT_ORG')) {
    // Load configuration for the cache.
    define('CACHE_PREFIX_ORG', '_' . PASSBOLT_ORG);
} else {
    // Define default cache for jobs that are not linked with an org.
    define('CACHE_PREFIX_ORG', '_passbolt');
}
