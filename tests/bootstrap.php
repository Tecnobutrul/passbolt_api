<?php
/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
define('TEST_IS_RUNNING', true);
define('PASSBOLT_ORG', 'acme');
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

$_SERVER['PHP_SELF'] = '/';
