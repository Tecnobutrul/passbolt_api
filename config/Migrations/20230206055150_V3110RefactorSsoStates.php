<?php
declare(strict_types=1);

use Cake\Log\Log;
use Migrations\AbstractMigration;
use Passbolt\Sso\Service\SsoAuthenticationTokens\DeleteSsoStateAuthenticationTokenService;

class V3110RefactorSsoStates extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $this
            ->table('sso_states', [
                'id' => false,
                'primary_key' => ['id'],
                'collation' => 'utf8mb4_unicode_ci'
            ])
            ->addColumn('id', 'uuid', [
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_general_ci'
            ])
            ->addColumn('nonce', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_general_ci',
            ])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 16,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_general_ci',
            ])
            ->addColumn('state', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_general_ci',
            ])
            ->addColumn('sso_settings_id', 'uuid', [
                'default' => null,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_general_ci',
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'null' => true,
                'encoding' => 'ascii',
                'collation' => 'ascii_general_ci',
            ])
            ->addColumn('user_agent', 'string', [
                'default' => null,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_general_ci',
            ])
            ->addColumn('ip', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('deleted', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex('nonce', ['unique' => true])
            ->addIndex('state', ['unique' => true])
            ->addIndex(['sso_settings_id', 'user_id'])
            ->create();

        try {
            (new DeleteSsoStateAuthenticationTokenService())->delete();
        } catch (Throwable $e) {
            Log::error('There was an error in V3110RefactorSsoStates');
            Log::error($e->getMessage());
        }
    }
}
