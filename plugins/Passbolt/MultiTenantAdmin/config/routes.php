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
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

/**
 * MultiTenant organizations prefixed routes
 */
Router::plugin('Passbolt/MultiTenantAdmin', ['path' => '/multi_tenant'], function (RouteBuilder $routes) {
    $routes->setExtensions(['json']);

    $routes->connect('/organizations/:id', ['controller' => 'Organizations', 'action' => 'view'])
            ->setPass(['id'])
           ->setMethods(['GET']);

    $routes->connect('/organizations', ['controller' => 'Organizations', 'action' => 'add'])
           ->setMethods(['POST']);
});
