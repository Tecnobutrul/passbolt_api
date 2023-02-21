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
 * @since         3.11.0
 */

namespace Passbolt\Sso\Controller\Azure;

use App\Controller\ErrorController as BaseErrorController;
use Cake\Event\EventInterface;

/**
 * Error Handling Controller
 *
 * Controller used by ExceptionRenderer to render error responses.
 */
class ErrorController extends BaseErrorController
{
    /**
     * {@inheritDoc}
     *
     * @psalm-suppress InvalidReturnType
     */
    public function beforeRender(EventInterface $event)
    {
        if ($this->request->is('json')) {
            parent::beforeRender($event);

            return;
        }

        $this->viewBuilder()
            ->setTemplatePath('Error')
            ->setTemplate('error');
    }
}
