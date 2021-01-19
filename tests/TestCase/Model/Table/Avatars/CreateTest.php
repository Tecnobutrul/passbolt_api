<?php
declare(strict_types=1);

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
 * @since         2.0.0
 */

namespace App\Test\TestCase\Model\Table\Avatars;

use App\Test\Lib\AppTestCase;
use App\Test\Lib\Model\AvatarsModelTrait;
use App\Utility\ImageStorage\GoogleCloudStorageListener;
use App\Utility\UuidFactory;
use Burzum\FileStorage\Storage\Listener\ImageProcessingListener;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventManager;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;

class CreateTest extends AppTestCase
{
    use AvatarsModelTrait;

    public $Avatars;

    public $fixtures = ['app.Base/Users', 'app.Base/Profiles', 'app.Base/Avatars'];

    public function setUp()
    {
        parent::setUp();

        $this->loadPlugins([
            'Burzum/FileStorage' => ['bootstrap' => false, 'routes' => true],
            'Burzum/Imagine' => ['bootstrap' => true, 'routes' => true],
        ]);
        $listener = new GoogleCloudStorageListener();
        EventManager::instance()->on($listener);
        $listener = new ImageProcessingListener();
        EventManager::instance()->on($listener);

        $this->Avatars = TableRegistry::getTableLocator()->get('Avatars');

        // delete default ada avatar / it may not be reachable
        $connection = ConnectionManager::get('test');
        $connection->delete('file_storage', ['user_id' => UuidFactory::uuid('user.id.ada')]);
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    private function _createAvatar($name = 'ada')
    {
        $userAvatarFullPath = FIXTURES . 'Avatar' . DS . $name . '.png';
        $data = [
            'file' => [
                'tmp_name' => $userAvatarFullPath,
                'error' => 0,
                'type' => 'image/png',
                'name' => $name . '.png',
            ],
            'user_id' => UuidFactory::uuid('user.id.' . $name),
            'foreign_key' => UuidFactory::uuid('profile.id.' . $name),
        ];

        $newAvatar = $this->Avatars->newEntity($data, ['validate' => false]);
        if (!$this->Avatars->save($newAvatar)) {
            $this->fail('Could not create avatar for testing');
        }

        return $newAvatar->toArray();
    }

    public function testCreateAvatarFileIsCreated()
    {
        $this->assertNotEmpty(Configure::read('ImageStorage.publicPath'));

        $avatar = $this->_createAvatar('ada');

        $http = new Client();
        $response = $http->get($avatar['url']['small']);
        $this->assertTrue($response->isOk());
        $response = $http->get($avatar['url']['medium']);
        $this->assertTrue($response->isOk());
    }

    public function testCreateAvatarDeleteFormerVersionAfterCreate()
    {
        $this->markTestIncomplete('To fix');
        $this->assertNotEmpty(Configure::read('ImageStorage.publicPath'));
        $avatar = $this->_createAvatar('ada');

        $http = new Client();
        $response = $http->get($avatar['url']['small']);
        $this->assertTrue($response->isOk());
        $response = $http->get($avatar['url']['medium']);
        $this->assertTrue($response->isOk());

        $avatar1 = $this->_createAvatar('ada');
        $response = $http->get($avatar1['url']['small']);
        $this->assertTrue($response->isOk());
        $response = $http->get($avatar1['url']['medium']);
        $response->isOk();

        // Assert that the previous avatar files have been deleted.
        $response = $http->get($avatar['url']['small']);
        $this->assertFalse($response->isOk());
        $response = $http->get($avatar['url']['medium']);
        $this->assertFalse($response->isOk());
    }
}
