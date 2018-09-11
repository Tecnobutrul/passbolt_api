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
namespace Passbolt\MultiTenantAdmin\Shell\Task;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Passbolt\MultiTenantAdmin\Utility\OrganizationManager;

/**
 * Migrate organizations shell command.
 */
class MigrateOrganizationsTask extends Shell
{
    /**
     * Gets the option parser instance and configures it.
     *
     * By overriding this method you can configure the ConsoleOptionParser before returning it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     * @link https://book.cakephp.org/3.0/en/console-and-shells.html#configuring-options-and-generating-help
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription(__('Migrate organizations.'));

        return $parser;
    }

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main()
    {
        $start = time();
        $OrganizationTable = TableRegistry::get('Organizations');
        $organizations = $OrganizationTable->find()->all();

        foreach($organizations as $org) {
            $this->out(__('migrating {0}', [$org->slug]));
            try {
                $organizationManager = new OrganizationManager($org->slug);
                $migrated = $organizationManager->migrate();
                if ($migrated !== true) {
                    $this->err(__('Could not migrate schema for {0}', [$org->slug]));
                }
            } catch (\Exception $e){
                $this->err(__('Could not migrate schema for {0}', [$org->slug]));
                $this->err($e->getMessage());
                $this->err($e->getTrace());
            }
            $this->info('[OK]');
        }
        $end = time();
        $total = $end - $start;
        $this->info('all migrations done in ' . $total . ' seconds');

        return true;
    }
}
