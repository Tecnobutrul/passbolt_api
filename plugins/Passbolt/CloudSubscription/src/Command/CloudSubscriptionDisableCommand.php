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
use Passbolt\CloudSubscription\Service\CloudSubscriptionSettings;

class CloudSubscriptionDisableCommand extends CloudSubscriptionCommand
{
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

        try {
            $expiryDate = Date::today();
            $subscription = new CloudSubscriptionSettings([
                'status' => CloudSubscriptionSettings::STATUS_DISABLED,
                'expiryDate' => $expiryDate->toUnixString(),
            ]);
            $subscription->save();
            $io->out(__('Subscription disabled for {0}.', $this->org));
        } catch (CustomValidationException $exception) {
            $this->displayErrors($exception, $io);
            $io->error(__('Fail to disable subscription. Could not validate data.'));
            $this->abort();
        }

        return null;
    }
}
