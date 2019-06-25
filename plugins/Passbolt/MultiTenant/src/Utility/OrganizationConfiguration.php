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
 * @since         2.11.0
 */
namespace Passbolt\MultiTenant\Utility;

use Cake\Utility\Hash;

class OrganizationConfiguration
{

    /**
     * Get a an organization database name.
     *
     * @param string $slug The organization slug
     * @return string
     */
    public static function getDatabaseName(string $slug)
    {
        $databasePrefix = env('PASSBOLT_PLUGINS_MULTITENANT_ORGANIZATION_DATABASEPREFIX', 'passbolt_cloud_organization_');

        return $databasePrefix . str_replace('-', '_', $slug);
    }

    /**
     * Get an organization datasource config.
     *
     * @param mixed $slug The organization slug. If not provided, duplicated the datasource defined by the connection name.
     * @param string $connectionName The target connection datasource to duplicate
     * @return array
     */
    public static function getDatabaseDatasource($slug = null, string $connectionName = 'default')
    {
        $appConfig = require CONFIG . DS . 'app.php';
        $passboltConfig = require CONFIG . DS . 'passbolt.php';
        $appTestDatasource = Hash::get($appConfig, "Datasources.$connectionName");
        $passboltTestDatasource = Hash::get($passboltConfig, "Datasources.$connectionName", []);
        $orgDatasource = array_merge($appTestDatasource, $passboltTestDatasource);
        if ($slug) {
            $orgDatasource['database'] = self::getDatabaseName($slug);
        }

        return $orgDatasource;
    }
}
