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
use Passbolt\MultiTenant\Utility\OrganizationConfiguration;

if (defined('PASSBOLT_ORG')) {
    return [
        'App' => [
            'fullBaseUrl' => Configure::read('App.fullBaseUrl') . '/' . PASSBOLT_ORG
        ],
        'Datasources' => [
            'default' => OrganizationConfiguration::getDatabaseDatasource(PASSBOLT_ORG)
        ]
    ];
}

return [];
