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
namespace Passbolt\CloudSubscription\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Passbolt\CloudSubscription\Command\Subcommands\TrialStartCommand;

class CloudSubscriptionCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->addArgument('action', [
            'help' => 'The action you want to perform'
        ]);
        $parser->addArgument('org', [
            'help' => 'The organization you are working with'
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $action = $args->getArgument('action');
        $org = $args->getArgument('org');
        if ($action) {
            $this->executeCommand(TrialStartCommand::class, [$org]);
        } else {
            $io->out("An action is required.");
        }
    }
}
