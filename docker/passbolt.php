<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
/**
 * PASSBOLT CONFIGURATION FILE TEMPLATE
 *
 * By default passbolt try to use the environment variables or fallback on the default values as
 * defined in default.php. You can use passbolt.default.php as a basis to set your own configuration
 * without using environment variables.
 *
 * 1. copy/paste passbolt.default.php to passbolt.php
 * 2. set the variables in the App section
 * 3. set the variables in the passbolt section
 *
 * To see all available options, you can refer to the default.php file, and modify passsbolt.php accordingly.
 * Do not modify default.php or you may break your upgrade process.
 *
 * Read more about how to install passbolt: https://www.passbolt.com/help/tech/install
 * Any issue, check out our FAQ: https://www.passbolt.com/faq
 * An installation issue? Ask for help to the community: https://community.passbolt.com/
 */
return [
    'debug' => false,

    /**
     * DEFAULT APP CONFIGURATION
     *
     * All the information in this section must be provided in order for passbolt to work
     * This configuration overrides the CakePHP defaults locating in app.php
     * Do not edit app.php as it may break your upgrade process
     */
    'App' => [
        // A base URL to use for absolute links.
        // The url where the passbolt instance will be reachable to your end users.
        // This information is need to render images in emails for example
        'fullBaseUrl' => 'https://passbolt.local',
    ],

    // Database configuration.
    'Datasources' => [
        'default' => [
            'host' => 'db',
            //'port' => 'non_standard_port_number',
            'username' => 'root',
            'password' => 'test',
            'database' => 'passbolt',
            //'database' => 'passbolt_test1',
        ],
        'test' => [
            'host' => 'db',
            //'port' => 'non_standard_port_number',
            'username' => 'root',
            'password' => 'test',
            'database' => 'passbolt_test',
            //'database' => 'passbolt_test1',
        ],
        'emailQueue' => [
            'className' => 'Cake\Database\Connection',
            'driver' => env('DATASOURCES_DEFAULT_DRIVER', 'Cake\Database\Driver\Mysql'),
            'persistent' => false,
            'host' => 'db',
            //'port' => 'non_standard_port_number',
            'username' => 'root',
            'password' => 'test',
            'database' => 'email_queue',
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'flags' => [],
            'cacheMetadata' => true,
            'log' => false,
        ],
    ],

    // Email configuration.
    'EmailTransport' => [
        'default' => [
            'host' => 'smtp.mandrillapp.com',
            'port' => 587,
            'username' => 'kevin@passbolt.com',
            'password' => 'mhYpdPx5NEEaYgPfIjyphw',
            // Is this a secure connection? true if yes, null if no.
            'tls' => null,
            //'timeout' => 30,
            //'client' => null,
            //'url' => null,
        ],
    ],
    'Email' => [
        'default' => [
            // Defines the default name and email of the sender of the emails.
            'from' => ['kevin@passbolt.com' => 'Passbolt'],
            //'charset' => 'utf-8',
            //'headerCharset' => 'utf-8',
        ],
    ],
//    // Which connection to use with emailQueue.
//    'EmailQueue' => [
//        'datasource' => 'emailQueue',
//    ],

    /**
     * DEFAULT PASSBOLT CONFIGURATION
     *
     * This is the default configuration.
     * It enforces the use of ssl, and does not provide a default OpenPGP key.
     * If your objective is to try passbolt quickly for evaluation purpose, and security is not important
     * you can use the demo config example provided in the next section below.
     */
    'passbolt' => [
        'selenium' => [
            'active' => true
        ],
        'ssl' => [
            'force' => false,
        ],
        'registration' => [
            'public' => true,
        ],
        'multiTenant' => [
            'configDir' => env('CONFIG_ORG', CONFIG . 'Org'),
            'auth' => [
                'secret' => '$argon2i$v=19$m=1024,t=2,p=2$ZC9iLnhUelIxQ2kvd3Q2QQ$RfOmHXc1/56v+Ljb90pY4z9ipNhQWSLvHuR5HA1wkcQ'
            ]
        ],
        // GPG Configuration.
        // The keyring must to be owned and accessible by the webserver user.
        // Example: www-data user on Debian
        'gpg' => [
            // Tell GPG where to find the keyring.
            // If putenv is set to false, gnupg will use the default path ~/.gnupg.
            // For example :
            // - Apache on Centos it would be in '/usr/share/httpd/.gnupg'
            // - Apache on Debian it would be in '/var/www/.gnupg'
            // - Nginx on Centos it would be in '/var/lib/nginx/.gnupg'
            // - etc.
            //'keyring' => getenv("HOME") . DS . '.gnupg',
            //
            // Replace GNUPGHOME with above value even if it is set.
            //'putenv' => false,

            // Main server key.
            'serverKey' => [
                // Server private key fingerprint.
                'fingerprint' => '2FC8945833C51946E937F9FED47B0811573EE67E',
                'public' => CONFIG . 'gpg' . DS . 'unsecure.key',
                'private' => CONFIG . 'gpg' . DS . 'unsecure_private.key',
            ],
        ],
    ],

/**
 * DEMO CONFIGURATION EXAMPLE
 *
 * Uncomment the lines below if you want to try passbolt quickly.
 * and if you are not concerned about the security of your installation.
 * (Don't forget to comment the default config above).
 */
//    'debug' => true,
//    'passbolt' => [
//        'registration' => [
//            'public' => true
//        ],
//        'ssl' => [
//            'force' => false,
//        ],
//        'gpg' => [
//            'serverKey' => [
//                'fingerprint' => '2FC8945833C51946E937F9FED47B0811573EE67E',
//                'public' => CONFIG . DS . 'gpg' . DS . 'unsecure.key',
//                'private' => CONFIG . DS . 'gpg' . DS . 'unsecure_private.key',
//            ],
//        ],
//    ]

];
