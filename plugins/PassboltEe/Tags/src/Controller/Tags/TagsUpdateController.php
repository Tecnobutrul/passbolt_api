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
 * @since         2.11.0
 */

namespace Passbolt\Tags\Controller\Tags;

use App\Controller\AppController;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Validation\Validation;
use Passbolt\Tags\Model\Entity\Tag;

/**
 * @property \Passbolt\Tags\Model\Table\TagsTable $Tags
 * @property \Passbolt\Tags\Model\Table\ResourcesTagsTable $ResourcesTags
 */
class TagsUpdateController extends AppController
{
    use TagAccessTrait;

    /**
     * Tag update action
     *
     * @param string|null $id Id of the tag to update
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException If the Tag is not found or the user does not have access.
     */
    public function update(?string $id = null)
    {
        if (!Validation::uuid($id)) {
            throw new BadRequestException(__('The tag id is not valid.'));
        }

        $this->loadModel('Passbolt/Tags.Tags');
        $this->loadModel('Passbolt/Tags.ResourcesTags');

        try {
            /** @var \Passbolt\Tags\Model\Entity\Tag $tag */
            $tag = $this->Tags->get($id, [
                'contain' => ['ResourcesTags'],
            ]);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('The tag does not exist.'));
        }

        if ($tag->get('is_shared')) {
            throw new ForbiddenException(__('You do not have the permission to update shared tags.'));
        }

        if (!$this->isPersonalTagAccessible($tag)) {
            throw new NotFoundException(__('The tag does not exist.'));
        }

        $updatedTag = $this->_updatePersonalTag($tag);

        $this->success(__('The tag has been updated successfully.'), $updatedTag);
    }

    /**
     * Update personal tag
     *
     * @param \Passbolt\Tags\Model\Entity\Tag $tag The tag to update
     * @return \Passbolt\Tags\Model\Entity\Tag|bool The updated tag
     * @throws \Cake\Http\Exception\BadRequestException If a non admin tries to change a personal tag into a shared tag.
     * @throws \Exception
     */
    private function _updatePersonalTag(Tag $tag)
    {
        $slug = $this->request->getData('slug');

        if (mb_substr($slug, 0, 1) === '#') {
            throw new BadRequestException('You do not have the permission to change a personal tag into shared tag.');
        }

        return $this->Tags->getConnection()->transactional(function () use ($tag, $slug) {
            $newTag = $this->Tags->findOrCreateTag($slug, $this->User->getAccessControl());

            // Update all the tag association to the new tag id
            $this->ResourcesTags->updateUserTag(
                $this->User->id(),
                $tag->get('id'),
                $newTag->get('id')
            );

            // Flush all unused tags
            $this->Tags->deleteAllUnusedTags();

            return $newTag;
        });
    }
}
