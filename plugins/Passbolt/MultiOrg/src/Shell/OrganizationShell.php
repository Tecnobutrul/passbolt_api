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
namespace Passbolt\MultiOrg\Shell;

use Cake\Console\Shell;

class OrganizationShell extends Shell
{
    /**
     * @var array of linked tasks
     */
    public $tasks = [
        'Passbolt/MultiOrg.Create',
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

        $parser->addSubcommand('create', [
            'help' => __d('cake_console', 'create a new organization.'),
            'parser' => $this->Create->getOptionParser(),
        ]);

        return $parser;
    }
}