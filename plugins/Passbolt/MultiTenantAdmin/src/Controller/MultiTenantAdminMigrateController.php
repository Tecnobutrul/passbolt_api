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

namespace Passbolt\MultiTenantAdmin\Controller;

use Cake\Http\Exception\InternalErrorException;
use Cake\Log\Log;
use Migrations\Migrations;
use Passbolt\MultiTenantAdmin\Lib\Controller\MultiTenantAdminController;
use function GuzzleHttp\json_encode;

class MultiTenantAdminMigrateController extends MultiTenantAdminController
{

    /**
     * The migration status
     *
     * @var array
     */
    protected $migrationStatus;

    /**
     * Run the migrations on the current organization.
     *
     * @return void
     */
    public function migrate()
    {
        if (!$this->request->is('json')) {
            throw new BadRequestException(__('This is not a valid Ajax/Json request.'));
        }

        if (!$this->runMigrations()) {
            throw new InternalErrorException(__("Unable to migrate the organization: {0}", [PASSBOLT_ORG]));
        }

        $this->success(__('The operation was successful.'), $this->migrationStatus);
    }

    /**
     * Run the migration on the database.
     *
     * @return bool
     */
    protected function runMigrations()
    {
        $migrations = new Migrations();
        // Do not remove this line. It forces the migration plugin to clear its cache regarding the conf.
        $migrations->status();
        $migrated = $migrations->migrate();
        $this->migrationStatus = $migrations->status();

        return $migrated;
    }
}
