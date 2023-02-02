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
 * @since         3.10.0
 */
namespace Passbolt\Ee\Test\TestCase;

use App\Test\TestCase\BaseSolutionBootstrapperTest;
use App\Utility\Application\FeaturePluginAwareTrait;
use Cake\Core\Configure;
use Cake\Core\PluginCollection;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Passbolt\Ee\EeSolutionBootstrapper;
use Passbolt\EmailDigest\Utility\Digest\DigestsPool;
use Passbolt\EmailNotificationSettings\Utility\EmailNotificationSettings;

/**
 * EeFeaturePluginAdder class
 *
 * @covers \Passbolt\Ee\EeSolutionBootstrapper
 * @group SolutionBootstrapper
 */
class EeSolutionBootstrapperTest extends TestCase
{
    use FeaturePluginAwareTrait;
    use IntegrationTestTrait;

    public const EXPECTED_EE_PLUGINS = [
        'Passbolt/DirectorySync',
        'Passbolt/Tags',
        'Passbolt/AuditLog',
        'Passbolt/Folders',
        'Passbolt/AccountRecovery',
        'Passbolt/Sso',
    ];

    /**
     * @var \App\Application
     */
    public $app;

    public function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApp();
        $this->clearPlugins();
        DigestsPool::clearInstance();
        EmailNotificationSettings::flushCache();
    }

    public function tearDown(): void
    {
        $this->clearPlugins();
        unset($this->app);
        parent::tearDown();
    }

    public function testEeSolutionBootstrapper_Application_Bootstrap(): void
    {
        Configure::delete('passbolt.webInstaller.configured');
        $plugins = $this->arrangeAndGetPlugins();
        $expectedPluginList = array_merge(
            [
                'Migrations',
                'Authentication',
                'EmailQueue',
                'BryanCrowe/ApiPagination',
                'Passbolt/Ee',
            ],
            BaseSolutionBootstrapperTest::EXPECTED_CE_PLUGINS,
            self::EXPECTED_EE_PLUGINS,
            [
                'Bake',
                'CakephpFixtureFactories',
            ]
        );
        foreach ($expectedPluginList as $pluginName) {
            $this->assertSame($pluginName, $plugins->current()->getName());
            $plugins->next();
        }
    }

    public function testEeSolutionBootstrapper_Application_Bootstrap_WebInstaller_Required(): void
    {
        Configure::write('passbolt.webInstaller.configured', false);
        $plugins = $this->arrangeAndGetPlugins();
        $expectedPluginList = [
            'Migrations',
            'Authentication',
            'EmailQueue',
            'BryanCrowe/ApiPagination',
            'Passbolt/Ee',
            'Passbolt/WebInstaller',
            'Bake',
            'CakephpFixtureFactories',
        ];
        foreach ($expectedPluginList as $pluginName) {
            $this->assertSame($pluginName, $plugins->current()->getName());
            $plugins->next();
        }
    }

    protected function arrangeAndGetPlugins(): PluginCollection
    {
        $this->enableFeaturePlugin('Mobile');
        $this->enableFeaturePlugin('JwtAuthentication');
        $this->enableFeaturePlugin('SmtpSettings');
        $this->enableFeaturePlugin('SelfRegistration');
        $this->enableFeaturePlugin('Tags');
        $this->enableFeaturePlugin('AccountRecovery');
        $this->enableFeaturePlugin('Sso');
        // These plugins are enabled by default if not defined
        Configure::delete('passbolt.plugins.ee.enabled');
        Configure::delete('passbolt.plugins.multiFactorAuthentication.enabled');
        Configure::delete('passbolt.plugins.log.enabled');
        Configure::delete('passbolt.plugins.directorySync.enabled');
        Configure::delete('passbolt.plugins.folders.enabled');

        $this->app->setSolutionBootstrapper(new EeSolutionBootstrapper());
        $this->app->bootstrap();
        $this->app->pluginBootstrap();
        $plugins = $this->app->getPlugins();
        $plugins->rewind();

        return $plugins;
    }
}
