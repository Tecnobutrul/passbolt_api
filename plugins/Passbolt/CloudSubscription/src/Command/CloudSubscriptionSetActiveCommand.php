<?php
/**
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\CloudSubscription\Command;

use App\Error\Exception\CustomValidationException;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Chronos\Date;
use Cake\Http\Exception\InternalErrorException;
use Passbolt\CloudSubscription\Service\CloudSubscriptionSettings;

class CloudSubscriptionSetActiveCommand extends CloudSubscriptionCommand
{
    /**
     * @inheritDoc
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
     * @inheritDoc
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
        } catch (CustomValidationException $exception) {
            $this->displayErrors($exception, $io);
            $io->error(__('Fail set subscription to active. Could not validate data.'));
            $this->abort();
        }

        $subscription->save();
        $io->out(__("Subscription is active for {0}. It expires: {1}", $this->org, $expiryDate->toIso8601String()));
    }
}
