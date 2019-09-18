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
use Passbolt\CloudSubscription\Service\CloudSubscriptionSettings;

class CloudSubscriptionDisableCommand extends CloudSubscriptionCommand
{
    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);

        try {
            $expiryDate = Date::today();
            $subscription = new CloudSubscriptionSettings([
                'status' => CloudSubscriptionSettings::STATUS_DISABLED,
                'expiryDate' => $expiryDate->toUnixString()
            ]);
        } catch (CustomValidationException $exception) {
            $this->displayErrors($exception, $io);
            $io->error(__('Fail to disable subscription. Could not validate data.'));
            $this->abort();
        }

        $subscription->save();
        $io->out(__("Subscription disabled for {0}.", $this->org));
    }
}
