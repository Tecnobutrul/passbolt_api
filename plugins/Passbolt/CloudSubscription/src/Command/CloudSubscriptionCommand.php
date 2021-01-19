<?php
declare(strict_types=1);

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
use Cake\Core\Configure;

class CloudSubscriptionCommand extends Command
{
    /**
     * @var string org
     */
    public $org;

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
        $parser->addOption('org', [
            'help' => 'The organization you are working with.',
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
        if (Configure::read('debug') !== true) {
            $io->error('This tool requires debug mode. Do not use in production. Use cloud admin commands instead.');
            $this->abort();
        }

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
     * @param \App\Error\Exception\CustomValidationException $exception exception
     * @param \Cake\Console\ConsoleIo $io io
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
