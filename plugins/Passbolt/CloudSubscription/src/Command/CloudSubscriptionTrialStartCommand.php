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
use Cake\Chronos\Date;
use Cake\Http\Exception\InternalErrorException;
use Passbolt\CloudSubscription\Utility\CloudSubscriptionSettings;

class CloudSubscriptionTrialStartCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->addOption('org', [
            'help' => 'The organization you are working with.'
        ]);
        $parser->addOption('expiryDate', [
            'help' => 'When the trial expires.'
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $org = $args->getOption('org');
        if (!isset($org)) {
            throw new InternalErrorException(__('Can not start trial. Organization is missing.'));
        }

        $expiryDate = $args->getOption('expiryDate');
        if (!isset($expiryDate)) {
            $expiryDate = Date::today();
            $expiryDate = $expiryDate->modify('+14 days');
        } else {
            $expiryDate = new Date($expiryDate);
        }

        $subscription = new CloudSubscriptionSettings([
            'isTrial' => true,
            'expiryDate' => $expiryDate->toUnixString()
        ]);
        $subscription->save();

        $io->out(__("Trial started for {$org}. Expires: {0}", $expiryDate->toIso8601String()));
    }
}
