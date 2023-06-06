<?php
declare(strict_types=1);

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
namespace Passbolt\Tags\Test\TestCase\Controller;

use App\Test\Factory\ResourceFactory;
use App\Utility\UuidFactory;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Passbolt\Tags\Test\Factory\ResourcesTagFactory;
use Passbolt\Tags\Test\Factory\TagFactory;
use Passbolt\Tags\Test\Lib\TagPluginIntegrationTestCase;

class ResourcesTagsAddControllerTest extends TagPluginIntegrationTestCase
{
    public $Tags;
    public $fixtures = [
        'app.Base/Users','app.Base/Roles', 'app.Base/Resources', 'app.Base/Groups',
        'app.Alt0/GroupsUsers', 'app.Alt0/Permissions',
        'plugin.Passbolt/Tags.Base/Tags', 'plugin.Passbolt/Tags.Alt0/ResourcesTags',
    ];

    // A "not found" error is returned if the resource does not exist

    public function testTagsResourcesTagsAddResourceDoesNotExistError()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.nope');
        $data = ['tags' => []];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $response = json_decode($this->_getBodyAsString());
        $this->assertError(404);
    }

    // A "not found" error is returned if the user does not have read access on the resource

    public function testTagsResourcesTagsAddNoResourcePermissionError()
    {
        $this->authenticateAs('dame');
        $resourceId = UuidFactory::uuid('resource.id.apache');
        $data = ['tags' => []];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertError(404);
    }

    // A user can add personal tags on a resource with read access

    public function testTagsResourcesTagsAddReadPermissionPersonalTagSuccess()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.bower');
        $data = ['tags' => ['tag1', '🤔']];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertSuccess();
        $response = json_decode($this->_getBodyAsString());
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertCount(2, $results);
        $this->assertContains('tag1', $results);
        $this->assertContains('🤔', $results);
    }

    // A user can add personal tags on a resource with read access (V1 format)

    public function testTagsResourcesTagsAddReadPermissionPersonalTagSuccess_v1()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.bower');
        $data = ['Tags' => ['tag1', '🤔']];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertSuccess();
        $response = json_decode($this->_getBodyAsString());
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertCount(2, $results);
        $this->assertContains('tag1', $results);
        $this->assertContains('🤔', $results);
    }

    // A user can not add shared tags on a resource with read access

    public function testTagsResourcesTagsAddReadPermissionSharedTagError()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.bower');

        $data = ['tags' => ['#tag1']];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertError(400);
    }

    // A user can not add shared tags on a resource with read access

    public function testTagsResourcesTagValidationError()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.bower');
        $data = ['tags' => [bin2hex(openssl_random_pseudo_bytes(256))]];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertError(400);
        $response = json_decode($this->_getBodyAsString());
        $msg = 'The tag length should be maximum 128 characters.';
        $this->assertEquals($response->body[0]->slug->maxLength, $msg);
    }

    // A user can add shared and personal tags on a resource it owns via direct permission

    public function testTagsResourcesTagsAddSuccess()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.apache');
        $data = ['tags' => ['#bravo', 'flip', '#stup', 'hotel']];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertSuccess();
        $response = json_decode($this->_getBodyAsString());
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertCount(4, $results);
        $this->assertContains('#bravo', $results);
        $this->assertContains('#stup', $results);
        $this->assertContains('flip', $results);
        $this->assertContains('hotel', $results);
    }

    // A user can add shared and personal tags on a resource it owns via group permission

    public function testTagsResourcesTagsAddSuccessGroupOwnership()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.kde');
        $data = ['tags' => ['#bravo', 'stup', 'flip']];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $response = json_decode($this->_getBodyAsString());
        $this->assertSuccess();
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertCount(3, $results);
        $this->assertContains('#bravo', $results);
        $this->assertContains('stup', $results);
        $this->assertContains('flip', $results);
    }

    // A user can add personal tags on a resource it can read via group permission

    public function testTagsResourcesTagsAddSuccessGroupRead()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.grogle');
        $data = ['tags' => ['#golf', 'stup', 'flip']];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $response = json_decode($this->_getBodyAsString());
        $this->assertSuccess();
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertCount(3, $results);
        $this->assertContains('#golf', $results);
        $this->assertContains('stup', $results);
        $this->assertContains('flip', $results);
    }

    // A user can delete shared and personal tags on a resource it owns via direct permission

    public function testTagsResourcesTagsAddSuccessDelete()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.apache');
        $data = ['tags' => []];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertSuccess();
        $response = json_decode($this->_getBodyAsString());
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertEquals($results, []);
    }

    // A user can delete shared and personal tags on a resource it owns via group permission

    public function testTagsResourcesTagsAddSuccessDeleteGroupOwnership()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.cakephp');
        $data = ['tags' => []];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertSuccess();
        $response = json_decode($this->_getBodyAsString());
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertEquals($results, []);
    }

    // A user deleting personal tags on a resource should not delete other users personal tags

    public function testTagsResourcesTagsAddSuccessDeleteKeepsOtherPeoplePersonalTags()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.chai');
        $data = ['tags' => []];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertSuccess();

        if (ConnectionManager::get('test')->getDriver() instanceof Postgres) {
            // On postgres, the tags are wrongly saved, assigning a tag to another user
            // This should be handled by refactoring the ResourcesTagsAdd controller. See PB-15260.
            return;
        }

        $response = json_decode($this->_getBodyAsString());
        $results = Hash::extract($response->body, '{n}.slug');
        $this->assertEquals($results, []);

        $ResourcesTags = TableRegistry::getTableLocator()->get('Passbolt/Tags.ResourcesTags');
        $rt = $ResourcesTags->query()
            ->where([
                'resource_id' => $resourceId,
                'user_id' => UuidFactory::uuid('user.id.betty'),
            ])
            ->all();

        $this->assertNotEmpty($rt);
    }

    // Unused tags should be deleted

    public function testTagsResourcesTagsCleanupSuccess()
    {
        $this->authenticateAs('ada');
        $resourceId = UuidFactory::uuid('resource.id.apache');
        $data = ['tags' => []];
        $this->postJson('/tags/' . $resourceId . '.json?api-version=2', $data);
        $this->assertSuccess();

        // Check tag cleanup
        // #bravo and alpha should still be there
        $this->Tags = TableRegistry::getTableLocator()->get('Passbolt/Tags.Tags');
        $this->Tags->get(UuidFactory::uuid('tag.id.#bravo'));
        $this->Tags->get(UuidFactory::uuid('tag.id.alpha'));

        // Fox-trot should have been deleted (not in used anymore)
        $this->expectException(RecordNotFoundException::class);
        $this->Tags->get(UuidFactory::uuid('tag.id.fox-trot'));
    }

    public function testResourcesTagsAddController_Success_SlugWithDifferentCase()
    {
        if (!ConnectionManager::get('default')->getDriver() instanceof Mysql) {
            $this->markTestSkipped('Only required to run with MySQL database driver');
        }

        $user = $this->logInAsUser();
        $resource = ResourceFactory::make()->withCreatorAndPermission($user)->persist();
        /** @var \Passbolt\Tags\Model\Entity\ResourcesTag $resourceTag */
        $resourceTag = ResourcesTagFactory::make(['resource_id' => $resource->id])
            ->with('Users', $user)
            ->with('Tags', ['slug' => 'test'])
            ->persist();
        ResourcesTagFactory::make()
            ->with('Users')
            ->with('Tags', $resourceTag->tag)
            ->persist();

        $this->postJson("/tags/{$resource->id}.json?api-version=2", [
            'tags' => ['TEST'],
        ]);

        $this->assertSuccess();
        $responseArray = $this->getResponseBodyAsArray();
        $this->assertCount(1, $responseArray);
        $this->assertNotSame($resourceTag->tag_id, $responseArray[0]['id']);
        $this->assertCount(2, TagFactory::find()->where(['slug' => 'test']));
    }
}
