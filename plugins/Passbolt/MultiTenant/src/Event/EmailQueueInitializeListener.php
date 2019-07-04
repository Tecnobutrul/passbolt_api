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
 * @since         2.11.0
 */
namespace Passbolt\MultiTenant\Event;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventListenerInterface;
use EmailQueue\Model\Table\EmailQueueTable;

class EmailQueueInitializeListener implements EventListenerInterface
{
    /**
     * Undocumented function
     *
     * @return void
     */
    public function implementedEvents()
    {
        return [
            'Model.initialize' => 'initializeEvent',
        ];
    }

    /**
     * Undocumented function
     *
     * @param Event $event Initialization event
     * @return void
     */
    public function initializeEvent($event)
    {
        $table = $event->getSubject();
        if ($table instanceof EmailQueueTable) {
            $emailQueueConf = Configure::read('EmailQueue');
            if (!empty($emailQueueConf) && !empty($emailQueueConf['datasource'])) {
                $datasourceName = $emailQueueConf['datasource'];
                $connection = ConnectionManager::get($datasourceName);
                $table->setConnection($connection);
            }
        }
    }
}
