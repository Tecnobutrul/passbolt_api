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
namespace Passbolt\Ee;

use App\Application;
use App\BaseSolutionBootstrapper;

class EeSolutionBootstrapper extends BaseSolutionBootstrapper
{
    /**
     * Loads all the plugins relative to the solution
     *
     * @param \App\Application $app Application
     * @return void
     */
    public function addFeaturePlugins(Application $app): void
    {
        $this->addFeaturePluginIfEnabled($app, 'Ee', [], true);

        if ($this->isWebInstallerConfigurationMissing()) {
            $app->addPlugin('Passbolt/WebInstaller', ['bootstrap' => true, 'routes' => true]);

            return;
        }

        // Add CE plugins
        parent::addFeaturePlugins($app);

        // Add EE plugins
        $this->addFeaturePluginIfEnabled($app, 'DirectorySync', [], true);
        $this->addFeaturePluginIfEnabled($app, 'Tags', [], true);
        if ($app->getPlugins()->has('Passbolt/Log')) {
            $app->addPlugin('Passbolt/AuditLog', ['bootstrap' => true, 'routes' => true]);
        }
        $this->addFeaturePluginIfEnabled($app, 'Folders', [], true);
        $this->addFeaturePluginIfEnabled($app, 'AccountRecovery', [], true);
        $this->addFeaturePluginIfEnabled($app, 'Sso');
    }
}
