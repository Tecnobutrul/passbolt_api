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
use Cake\Http\Exception\InternalErrorException;

class CloudSubscriptionCommand extends Command
{
    /** @var string org */
    public $org;

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->addOption('org', [
            'help' => 'The organization you are working with.'
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->org = $args->getOption('org');
        if (!isset($this->org)) {
            $io->error(__('Can not run command. Organization is missing.'));
            $this->abort();
        }
    }

    /**
     * @param CustomValidationException $exception
     * @param ConsoleIo $io
     */
    protected function displayErrors(CustomValidationException $exception, ConsoleIo $io)
    {
        $errors = $exception->getErrors();
        foreach ($errors as $field => $error) {
            foreach ($error as $rule => $message) {
                $io->out($field . ': ' . $message);
            }
        }
    }
}
