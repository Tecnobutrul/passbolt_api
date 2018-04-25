<?php
if (!defined('CONFIG_ORG')) {
    if (!getenv('CONFIG_ORG')) {
        define('CONFIG_ORG', __DIR__ . DS . 'Org');
    } else {
        define('CONFIG_ORG', getenv('CONFIG_ORG'));
    }
}
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
    echo 'This organization is missing.';
    trigger_error('This organization is missing.', E_USER_ERROR);
    exit;
}