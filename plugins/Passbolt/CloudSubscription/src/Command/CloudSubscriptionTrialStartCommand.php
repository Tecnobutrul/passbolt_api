<?php
declare(strict_types=1);

/**
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\CloudSubscription\Command;

use App\Error\Exception\CustomValidationException;
use Cake\Chronos\Date;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Passbolt\CloudSubscription\Service\CloudSubscriptionSettings;

class CloudSubscriptionTrialStartCommand extends CloudSubscriptionCommand
{
    /**
     * Get the option parser.
     *
     * You can override buildOptionParser() to define your options & arguments.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser parser
     * @return \Cake\Console\ConsoleOptionParser $parser parser
     * @throws \RuntimeException When the parser is invalid
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser = parent::buildOptionParser($parser);
        $parser->addOption('expiryDate', [
            'help' => 'When the trial expires.',
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);
        $expiryDate = $args->getOption('expiryDate');
        if (!isset($expiryDate)) {
            $expiryDate = Date::today();
            $expiryDate = $expiryDate->modify(CloudSubscriptionSettings::TRIAL_DURATION);
        } else {
            $expiryDate = new Date($expiryDate);
        }
        try {
            $subscription = new CloudSubscriptionSettings([
                'status' => CloudSubscriptionSettings::STATUS_TRIAL,
                'expiryDate' => $expiryDate->toUnixString(),
            ]);
            $subscription->save();
            $io->out(__('Trial started for {0}. Expires: {1}', $this->org, $expiryDate->toIso8601String()));
        } catch (CustomValidationException $exception) {
            $this->displayErrors($exception, $io);
            $io->error(__('Fail to start trial. Could not validate data.'));
            $this->abort();
        }

        return null;
    }
}
