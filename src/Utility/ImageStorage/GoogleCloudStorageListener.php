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
namespace App\Utility\ImageStorage;

use Burzum\FileStorage\Storage\Listener\LocalListener;

/**
 * FileStorage Event Listener for the CakePHP FileStorage plugin
 */
class GoogleCloudStorageListener extends LocalListener
{
    // @codingStandardsIgnoreStart
    /**
     * List of adapter classes the event listener can work with.
     *
     * @var array
     */
    public $_adapterClasses = [
        '\Gaufrette\Adapter\Local',
        '\Gaufrette\Adapter\GoogleCloudStorage'
    ];
    // @codingStandardsIgnoreEnd
}
