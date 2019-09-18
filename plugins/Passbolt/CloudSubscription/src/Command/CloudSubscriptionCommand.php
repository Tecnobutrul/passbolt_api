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

class CloudSubscriptionCommand extends Command
{
    /** @var string org */
    public $org;

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
        $parser->addOption('org', [
            'help' => 'The organization you are working with.'
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
        $this->org = $args->getOption('org');
        if (!isset($this->org)) {
            $io->error(__('Can not run command. Organization is missing.'));
            $this->abort();
        }

        return null;
    }

    /**
     * Display errors
     *
     * @param CustomValidationException $exception exception
     * @param ConsoleIo $io io
     * @return void
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
