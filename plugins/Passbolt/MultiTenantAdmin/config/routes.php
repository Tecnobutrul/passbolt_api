<?php
/**
 * Passbolt Cloud
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin('Passbolt/MultiTenantAdmin', ['path' => '/multitenant/admin'], function (RouteBuilder $routes) {
    $routes->setExtensions(['json']);

    $routes->connect('/migrate', ['controller' => 'MultiTenantAdminMigrate', 'action' => 'migrate'])
        ->setMethods(['GET']);

    $routes->connect('/register-first-user', ['controller' => 'MultiTenantAdminRegisterFirstUser', 'action' => 'registerFirstUser'])
        ->setMethods(['POST']);

    $routes->connect('/subscriptions', ['controller' => 'MultiTenantAdminSubscriptionUpdate', 'action' => 'updateOrCreate'])
        ->setMethods(['POST']);
});
