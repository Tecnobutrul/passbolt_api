<?php
declare(strict_types=1);

use Cake\Log\Log;
use Migrations\AbstractMigration;
use Passbolt\MultiFactorAuthentication\Service\MfaOrgSettings\MfaOrgSettingsMigrationToDbService;

class V380SaveMfaOrganizationSettingsInDb extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        try {
            (new MfaOrgSettingsMigrationToDbService())->migrate();
        } catch (\Throwable $e) {
            Log::error('There was a migration error in V380SaveMfaOrganizationSettingsInDb.');
            Log::error($e->getMessage());
        }
    }
}
