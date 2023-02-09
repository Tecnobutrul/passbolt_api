<?php
declare(strict_types=1);

namespace Passbolt\Sso\Test\TestCase\Model\Table;

use Cake\ORM\Locator\LocatorAwareTrait;
use Passbolt\Sso\Test\Lib\SsoTestCase;

/**
 * @see \Passbolt\Sso\Model\Table\SsoStatesTable
 */
class SsoStatesTableTest extends SsoTestCase
{
    use LocatorAwareTrait;

    /**
     * Test subject
     *
     * @var \Passbolt\Sso\Model\Table\SsoStatesTable
     */
    protected $SsoStates;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->SsoStates = $this->fetchTable('PassboltEe/Sso.SsoStates');
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        unset($this->SsoStates);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \Passbolt\Sso\Model\Table\SsoStatesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \Passbolt\Sso\Model\Table\SsoStatesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
