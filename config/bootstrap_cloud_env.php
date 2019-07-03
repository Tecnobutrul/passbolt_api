<?php

/*
 * Before bootstraping cakephp :
 * - Make sure an organization slug is defined;
 * - Make sure the organization slug is valid.
 */

$isCli = PHP_SAPI === 'cli';
$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];

if (!defined('PASSBOLT_PLUGINS_MULTITENANT_NOORGREDIRECT')) {
    $noOrgRedirectUrl = env('PASSBOLT_PLUGINS_MULTITENANT_NOORGREDIRECT', 'https://www.passbolt.com/cloud/signup');
    define('PASSBOLT_PLUGINS_MULTITENANT_NOORGREDIRECT', $noOrgRedirectUrl);
}

if (!defined('PASSBOLT_ORG')) {
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
        $passboltOrg = null;
        $match = (explode('/', $_SERVER['REQUEST_URI'], 3));
        if (isset($match[1]) && !empty($match[1])) {
            $passboltOrg = $match[1];
        }

        // Redirect the user if no organization slug given in the url.
        if (!$passboltOrg) {
            header('Location: ' . PASSBOLT_PLUGINS_MULTITENANT_NOORGREDIRECT, true, 302);
            exit;
        }

        // Redirect the user if the organization slug does not validate.
        if (!preg_match('/^[a-z0-9]+[a-z0-9\-_]*[a-z0-9]+$/i', $passboltOrg)) {
            header('Location: ' . PASSBOLT_PLUGINS_MULTITENANT_NOORGREDIRECT, true, 302);
            exit;
        }

        define('PASSBOLT_ORG', $passboltOrg);
    }
}

// Set the cache prefix to use for this org
if (defined('PASSBOLT_ORG')) {
    // Load configuration for the cache.
    define('CACHE_PREFIX_ORG', '_' . PASSBOLT_ORG);
} else {
    define('CACHE_PREFIX_ORG', '_passbolt');
}
