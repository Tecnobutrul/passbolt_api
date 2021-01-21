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
 * @since         2.0.0
 */
namespace App\Controller\Healthcheck;

use App\Controller\AppController;
use App\Utility\OpenPGP\OpenPGPBackendFactory;
use Cake\Event\Event;

class HealthcheckDebugController extends AppController
{
    /**
     * Before filter
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(Event $event)
    {
        $this->Auth->allow(['debug']);

        return parent::beforeFilter($event);
    }

    /**
     * A lightweight method that returns OK
     * Useful to know if the site is up or not
     *
     * @return void
     */
    public function debug()
    {
        // TODO REMOVE
        $gpg = OpenPGPBackendFactory::get();
        try {
            $info = $gpg->getKeyInfo("-----BEGIN PGP PUBLIC KEY BLOCK-----\r\nVersion: OpenPGP.js v4.10.9\r\nComment: https://openpgpjs.org\r\n\r\nxsBNBGAJELYBCACdFOndRDw6PQcCD+CVtTEuE3kZ8cvWcjKoylHdzE2Efee3\r\nEzOBiL6W+QHcRH+y3m4bcTwj9QUFWOELDbv9mmn2Bw4ACfd7qe6UwTUh687o\r\nO960N0Lhd0iEcRBLp5PgB+pPC8Z3D2IXQQYvrfDQH3Q/ZjFa7aLGUHo1UvPV\r\nvEYYmbz+HbQXGu0iqRHDYymwIake0LXl+TCD+fBNpf3P+LrnH3nKR7GMNXO8\r\nIKUBcD/aMX7CVqGWDaUDJoTaFocEbhdQPAbx2nGcj9xoEbmUA7caVwtCDIXQ\r\nGAWKW4Ly3XEMCsjUoXfX/kIw2ZBYAQv1Clur9Yp1uZv23AR0EsmnDXSnABEB\r\nAAHNJFJlbXkgVGVzdCA8cmVteSt0ZXN0djNAcGFzc2JvbHQuY29tPsLAjQQQ\r\nAQgAIAUCYAkQtgYLCQcIAwIEFQgKAgQWAgEAAhkBAhsDAh4BACEJEDmJ+gKY\r\nu1ACFiEEKshmjJGadFMJwFKnOYn6Api7UAIQfAgAkan+x8LAtOwEvdpoeMwr\r\npO46iq5cHtS7bl9Qn6z6kErrfbuXkDvzdEQyI6UqBxH2z0raxvIign06yCHf\r\nXUbLlEvVk0TOC9nFgtpdE4vucREs9vC39yW8QUAgUIuEtLRQmD3FpFNNnrtO\r\nlWOjBTU49ZQdk7/KwHFiBKIhvBDx2uWRIZJ3TuphnACbEeHsvS0Rr6gVarst\r\nqev3AipRneV+JQSLtnkjJP4qi5IZcwsrzriW/mrOHMK+oii2dx6vUudTerKl\r\nETVQFTSKeOlI2faO0BoxvViC9i3t5l43Q7ph5/hSufkcXOngc95SVlsWHgIk\r\nWTj6CplZJ/T39IXVIPr4WM7ATQRgCRC2AQgAzwu2jCcHABIqtNffsDVT0BpL\r\ncEpdcoukfW6jpat5XL+i2W/8GCSvw6QSgbKIm1hIItmI1grErNv+F+e8DJPy\r\nfI/QuPqetD9zQP6PTbn963nUcJmjwbrcm4I+mRky3JJHt1e3ReKSOb9oAVDI\r\noYGDuR5HGvPHHWT/h7e9bv/yCB1EjEIuLF1DNTScUMIuLEhPAoY4eeRqIHRf\r\nyO8eWBRI/MrztBqNd0H4gf/PklYi/i+Y03XUYyGsHcxMwDYzuMWkKr5W9I/G\r\nrcjTyxCoJweeyS0aR3MjJxbEXCt4faqfaQ62yGoEgCXYa6YTtCh52X63p8KT\r\nU+xV7EYjLfagGk2DsQARAQABwsB2BBgBCAAJBQJgCRC2AhsMACEJEDmJ+gKY\r\nu1ACFiEEKshmjJGadFMJwFKnOYn6Api7UAKXLgf8CwOYTQfiesjkJgewFi5s\r\n/8oPqueTz9/qqMNJTg2nVo7gSAwl+/R8linXPd/kU8RCZ1OvcT8RQiJqjT0j\r\nXKQ2++N2fihttkH/ZIPDgB2aJeYIHablP2UN1g75r6zAqaXp/Z34MbgH7D2r\r\n8Gke9CAfAyQHOLfiEz8418W1EVR56VKg9cPD2Y+nKIJA1iLG9cY7yTcdkwjj\r\nweHl5mBEQia0q4Ij35t/eD/h7Vm9nvB/+TlunRB4XSYFaHPtch0NkzyBlMnE\r\nYxu3GvAkZHABvD9rs3kYVX5SNw/Q7j2wyOzTtW1iirW1PkUSlA3wqaaz/ViI\r\neknbNzGnT8xKL/83D+n9Bw==\r\n=/CRW\r\n-----END PGP PUBLIC KEY BLOCK-----\r\n");
            $this->success(__('OK'), $info);
        } catch(\Exception $e) {
            $this->error('FAIL', $e->getMessage());
        }
    }
}
