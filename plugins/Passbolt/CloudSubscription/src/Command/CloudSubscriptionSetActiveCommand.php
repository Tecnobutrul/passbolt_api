<?php
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

class CloudSubscriptionSetActiveCommand extends CloudSubscriptionCommand
{
    /**
     * Get the option parser.
     *
     * You can override buildOptionParser() to define your options & arguments.
     *
     * @param ConsoleOptionParser $parser parser
     * @return ConsoleOptionParser $parser parser
     * @throws \RuntimeException When the parser is invalid
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser = parent::buildOptionParser($parser);
        $parser->addOption('expiryDate', [
            'help' => 'When the subscription expires.'
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param Arguments $args The command arguments.
     * @param ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);

        $expiryDate = $args->getOption('expiryDate');
        if (!isset($expiryDate)) {
            $io->error(__('Fail to set subscription to active, expiry date is missing'));
            $this->abort();
        }

        try {
            $expiryDate = new Date($expiryDate);
            $subscription = new CloudSubscriptionSettings([
                'status' => CloudSubscriptionSettings::STATUS_ACTIVE,
                'expiryDate' => $expiryDate->toUnixString()
            ]);
            $subscription->save();
            $io->out(__("Subscription is active for {0}. It expires: {1}", $this->org, $expiryDate->toIso8601String()));
        } catch (CustomValidationException $exception) {
            $this->displayErrors($exception, $io);
            $io->error(__('Fail set subscription to active. Could not validate data.'));
            $this->abort();
        }

        return null;
    }
}
