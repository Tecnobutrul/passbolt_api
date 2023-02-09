<?php
declare(strict_types=1);

namespace Passbolt\Sso\Test\Factory;

use App\Utility\UuidFactory;
use Cake\Chronos\Chronos;
use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;
use Faker\Generator;
use Passbolt\Sso\Model\Entity\SsoState;
use Passbolt\Sso\Model\Table\SsoStatesTable;

/**
 * SsoStateFactory
 *
 * @method \Cake\ORM\Entity getEntity()
 * @method \Cake\ORM\Entity[] getEntities()
 * @method \Cake\ORM\Entity|\Cake\ORM\Entity[] persist()
 * @method static \Cake\ORM\Entity get(mixed $primaryKey, array $options = [])
 */
class SsoStateFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return SsoStatesTable::class;
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
                'nonce' => UuidFactory::uuid(),
                'type' => SsoState::TYPE_SSO_STATE,
                'state' => UuidFactory::uuid(),
                'sso_settings_id' => UuidFactory::uuid(),
                'user_id' => UuidFactory::uuid(),
                'user_agent' => $faker->userAgent(),
                'ip' => $faker->ipv4(),
                'created' => Chronos::now()->subDay(3),
                'deleted' => null,
            ];
        });
    }

    /**
     * @param string $type Type to set.
     * @return $this
     */
    public function type(string $type)
    {
        return $this->patchData(['type' => $type]);
    }

    /**
     * @param string $ssoSettingsId SSO settings ID.
     * @return $this
     */
    public function ssoSettingsId(string $ssoSettingsId)
    {
        return $this->patchData(['sso_settings_id' => $ssoSettingsId]);
    }

    /**
     * @param string $userId user ID
     * @return $this
     */
    public function userId(string $userId)
    {
        return $this->patchData(['user_id' => $userId]);
    }

    /**
     * @return $this
     */
    public function deleted()
    {
        return $this->setField('deleted', Chronos::now());
    }
}
