<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 */
namespace Passbolt\MultiTenantAnalytics\Shell\Task;

use App\Shell\AppShell;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;

class SendTask extends AppShell
{
    protected $Groups;
    protected $Users;

    /**
     * Initialize.
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->Users = TableRegistry::getTableLocator()->get('Users');
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * By overriding this method you can configure the ConsoleOptionParser before returning it.
     *
     * @throws \Exception
     * @return \Cake\Console\ConsoleOptionParser
     * @link https://book.cakephp.org/3.0/en/console-and-shells.html#configuring-options-and-generating-help
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->setDescription(__('Get analytics from the database and send them to a configurable external entry point.'));

        return $parser;
    }

    /**
     * Main shell entry point
     *
     * @return bool true if successful
     * @throws \Exception
     */
    public function main()
    {
        $data = $this->getData();
        $this->publish($data);

        return true;
    }

    /**
     * get data that will be sent to the entry point.
     * @return array
     */
    public function getData()
    {
        $data = [
            "org" => [
                "slug" => PASSBOLT_ORG
            ],
            "analytics" => $this->getAnalytics(),
        ];

        return $data;
    }

    /**
     * Get analytics in the expected format.
     * @return array
     */
    public function getAnalytics()
    {
        return [
            'active_users_count' => $this->getActiveUsersCount()
        ];
    }

    /**
     * Get active users count for the current organization.
     * @return mixed
     */
    public function getActiveUsersCount()
    {
        $activeUsersCount = $this->Users->find('all', [
                'conditions' => [
                    'Users.deleted' => false,
                    'Users.active' => true,
                ]
            ])->count();

        return $activeUsersCount;
    }

    /**
     * Publish analytics.
     * @param array $data data
     * @return Client\Response
     */
    public function publish(array $data)
    {
        $url = Configure::read('passbolt.multiTenantAnalytics.entryPoint.url');
        $username = Configure::read('passbolt.multiTenantAnalytics.entryPoint.username');
        $password = Configure::read('passbolt.multiTenantAnalytics.entryPoint.password');

        $http = new Client();
        $response = $http->post($url, $data, [
            'auth' => [
                'username' => $username,
                'password' => $password
            ]
        ]);

        return $response;
    }
}
