<?php
declare(strict_types=1);

namespace Passbolt\Tags\Test\Factory;

use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;
use Faker\Generator;
use Passbolt\Tags\Model\Entity\Tag;

/**
 * TagFactory
 *
 * @method \Passbolt\Tags\Model\Entity\Tag getEntity()
 * @method \Passbolt\Tags\Model\Entity\Tag[] getEntities()
 * @method \Passbolt\Tags\Model\Entity\Tag|\Passbolt\Tags\Model\Entity\Tag[] persist()
 * @method static \Passbolt\Tags\Model\Entity\Tag get(mixed $primaryKey, array $options = [])
 */
class TagFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Passbolt/Tags.Tags';
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
                'slug' => $faker->text(128),
            ];
        });
    }

    /**
     * Extend a resource factory and create a resource tag and a tag for each given users.
     *
     * @param CakephpBaseFactory $factory The factory to decorate
     * @param array $users The users that will own a tag
     * @param Tag|null $tag (Optional) If given, use it to mark the resource with, otherwise create a different tag for each user.
     * @return CakephpBaseFactory
     */
    public static function decorateResourceFactoryWithPersonalTagsFor(CakephpBaseFactory $factory, array $users, ?Tag $tag = null): CakephpBaseFactory
    {
        foreach ($users as $user) {
            $resourceTagFactory = ResourcesTagFactory::make()
                ->user($user);

            if ($tag) {
                $resourceTagFactory->tag($tag);
            } else {
                $resourceTagFactory->with('Tags');
            }

            $factory->with(
                'ResourcesTags',
                $resourceTagFactory
            );
        }

        return $factory;
    }
}
