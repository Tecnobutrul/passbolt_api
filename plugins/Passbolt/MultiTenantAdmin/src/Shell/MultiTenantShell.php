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
namespace Passbolt\MultiTenantAdmin\Shell;

use Cake\Console\Shell;

class MultiTenantShell extends Shell
{
    /**
     * @var array of linked tasks
     */
    public $tasks = [
        'Passbolt/MultiTenantAdmin.AddOrganization',
    ];

    /**
     * Get command options parser
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->setDescription(__('The Organization CLI offers an easy way to manage organizations.'));

        $parser->addSubcommand('add_organization', [
            'help' => __d('cake_console', 'add a new organization.'),
            'parser' => $this->AddOrganization->getOptionParser(),
        ]);

        return $parser;
    }
}
