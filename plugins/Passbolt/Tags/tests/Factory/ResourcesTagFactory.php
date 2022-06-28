<?php
declare(strict_types=1);

namespace Passbolt\Tags\Test\Factory;

use App\Model\Entity\User;
use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;
use Faker\Generator;
use Passbolt\Tags\Model\Entity\Tag;

/**
 * ResourcesTagFactory
 *
 * @method \Passbolt\Tags\Model\Entity\ResourcesTag getEntity()
 * @method \Passbolt\Tags\Model\Entity\ResourcesTag[] getEntities()
 * @method \Passbolt\Tags\Model\Entity\ResourcesTag|\Passbolt\Tags\Model\Entity\ResourcesTag[] persist()
 * @method static \Passbolt\Tags\Model\Entity\ResourcesTag get(mixed $primaryKey, array $options = [])
 */
class ResourcesTagFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Passbolt/Tags.ResourcesTags';
    }

    /**
     * Defines the factory's default values. This is useful for
     * not nullable fields. You may use methods of the present factory here too.
     *
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        $this->setDefaultData(function (Generator $faker) {
            return [
                // set the model's default values
                // For example:
                // 'name' => $faker->lastName
                'tag_id' => $faker->uuid(),
                'user_id' => $faker->uuid(),
                'resource_id' => $faker->uuid(),
            ];
        });
    }

    /**
     * Define the user to associate to the resource tag.
     *
     * @param User $user User to use
     * @return ResourcesTagFactory
     */
    public function user(User $user): self
    {
        $this->patchData(['user_id' => $user->id]);

        return $this;
    }

    /**
     * Define the tag to associate to the resource tag.
     *
     * @param Tag $tag Tag to use
     * @return ResourcesTagFactory
     */
    public function tag(Tag $tag): self
    {
        $this->patchData(['tag_id' => $tag->id]);

        return $this;
    }
}
