<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
// @codingStandardsIgnoreStart
use Migrations\AbstractMigration;

class V200MultiTenantAdminInitialMigration extends AbstractMigration
{

    protected function init() {
        parent::init();
    }

    /**
     * Up
     *
     * @return void
     */
    public function up()
    {
        $this->table('organizations', ['id' => false, 'primary_key' => ['id'], 'collation' => 'utf8mb4_unicode_ci'])
             ->addColumn('id', 'char', [
                 'default' => null,
                 'limit' => 36,
                 'null' => false,
             ])
             ->addColumn('slug', 'string', [
                 'default' => null,
                 'limit' => 128,
                 'null' => false,
             ])
            ->addColumn('plan', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => false,
            ])
             ->addColumn('max_users', 'boolean', [
                 'default' => 0,
                 'limit' => null,
                 'null' => false,
             ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
             ->addIndex([
                 'id',
                 'slug'
             ])
             ->create();
    }
}
// @codingStandardsIgnoreEnd