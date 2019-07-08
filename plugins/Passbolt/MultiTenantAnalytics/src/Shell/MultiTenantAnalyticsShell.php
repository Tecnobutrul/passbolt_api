<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA(https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA(https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\MultiTenantAnalytics\Shell;

use App\Shell\AppShell;

class MultiTenantAnalyticsShell extends AppShell
{
    /**
     * @var array of linked tasks
     */
    public $tasks = [
        'Passbolt/MultiTenantAnalytics.Send',
    ];

    /**
     * Initialize.
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        if (!$this->assertNotRoot()) {
            $this->abort(__('aborting'));
        }
    }

    /**
     * Get command options parser
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->setDescription(__('The multitenant analytics shell provides maintenance tasks for orgs managed by the multitenant plugin.'));

        $parser->addSubcommand('send', [
            'help' => __d('cake_console', 'Collect analytics and send them to the configured external entry point.'),
            'parser' => $this->Send->getOptionParser(),
        ]);

        return $parser;
    }
}
