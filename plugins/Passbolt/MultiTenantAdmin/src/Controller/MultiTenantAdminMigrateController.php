<?php
declare(strict_types=1);

/**
 * Passbolt Cloud
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\MultiTenantAdmin\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Migrations\Migrations;
use Passbolt\MultiTenantAdmin\Lib\Controller\MultiTenantAdminController;

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
            throw new InternalErrorException(__('Unable to migrate the organization: {0}', [PASSBOLT_ORG]));
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
