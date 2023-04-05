<?php
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

use App\Model\Entity\AuthenticationToken;
use App\Utility\AuthToken\AuthTokenExpiryConfigValidator;
use Passbolt\JwtAuthentication\Service\AccessToken\JwtAbstractService;
use Passbolt\Sso\Model\Entity\SsoSetting;
use Passbolt\Sso\Model\Entity\SsoState;

$authTokenExpiryConfigValidator = new AuthTokenExpiryConfigValidator();

return [
    /*
     * Passbolt application default configuration.
     * In alphabetical order:
     * - Authentication
     * - Email notifications
     * - Javascript application config
     * - Meta HTML tags
     * - Gpg
     * - Selenium mode
     * - Security settings
     * - SSL
     *
     * Pick a section and place it in your passbolt.php file to replace default settings.
     * Do not modify directly the values below as it will break passbolt update process.
     *
     */
    'passbolt' => [
        // Edition.
        'edition' => 'pro',
        'featurePluginAdder' => \Passbolt\Ee\EeSolutionBootstrapper::class,

        // Authentication & Authorisation.
        'auth' => [
            'tokenExpiry' => env('PASSBOLT_AUTH_TOKEN_EXPIRY', '3 days'),
            'token' => [
                AuthenticationToken::TYPE_REGISTER => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_REGISTER_TOKEN_EXPIRY', '10 days'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                AuthenticationToken::TYPE_RECOVER => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_RECOVER_TOKEN_EXPIRY', '10 days'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                AuthenticationToken::TYPE_LOGIN => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_LOGIN_TOKEN_EXPIRY', '5 minutes'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                AuthenticationToken::TYPE_MOBILE_TRANSFER => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_MOBILE_TRANSFER_TOKEN_EXPIRY', '5 minutes'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                AuthenticationToken::TYPE_REFRESH_TOKEN => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_JWT_REFRESH_TOKEN', '1 month'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                JwtAbstractService::USER_ACCESS_TOKEN_KEY => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_JWT_ACCESS_TOKEN', '5 minutes'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                AuthenticationToken::TYPE_VERIFY_TOKEN => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_JWT_VERIFY_TOKEN', '1 hour'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                SsoState::TYPE_SSO_SET_SETTINGS => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_SSO_SET_SETTINGS', '10 minutes'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                SsoState::TYPE_SSO_GET_KEY => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_SSO_GET_KEY', '10 minutes'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
                SsoState::TYPE_SSO_STATE => [
                    'expiry' => filter_var(env('PASSBOLT_AUTH_SSO_STATE', '10 minutes'), FILTER_CALLBACK, ['options' => $authTokenExpiryConfigValidator])
                ],
            ]
        ],

        // Email settings
        'email' => [
            // Additional email validation settings
            'validate' => [
                'mx' => filter_var(env('PASSBOLT_EMAIL_VALIDATE_MX', false), FILTER_VALIDATE_BOOLEAN),
                'regex' => env('PASSBOLT_EMAIL_VALIDATE_REGEX'),
            ],
            'purify' => [
                'subject' => filter_var(env('PASSBOLT_EMAIL_PURIFY_SUBJECT', false), FILTER_VALIDATE_BOOLEAN),
            ],

            // Email delivery settings such as credentials are in app.php.
            // Allow to disable displaying the armored secret in the email.
            // WARNING: make sure you have backups in place if you disable these.
            // See. https://www.passbolt.com/help/tech/backup
            'show' => [
                'comment' => filter_var(env('PASSBOLT_EMAIL_SHOW_COMMENT', false), FILTER_VALIDATE_BOOLEAN),
                'description' => filter_var(env('PASSBOLT_EMAIL_SHOW_DESCRIPTION', false), FILTER_VALIDATE_BOOLEAN),
                'secret' => filter_var(env('PASSBOLT_EMAIL_SHOW_SECRET', false), FILTER_VALIDATE_BOOLEAN),
                'uri' => filter_var(env('PASSBOLT_EMAIL_SHOW_URI', false), FILTER_VALIDATE_BOOLEAN),
                'username' => filter_var(env('PASSBOLT_EMAIL_SHOW_USERNAME', false), FILTER_VALIDATE_BOOLEAN),
            ],
            // Choose which emails are sent system wide.
            'send' => [
                'comment' => [
                    'add' => filter_var(env('PASSBOLT_EMAIL_SEND_COMMENT_ADD', true), FILTER_VALIDATE_BOOLEAN)
                ],
                'password' => [
                    'create' => filter_var(env('PASSBOLT_EMAIL_SEND_PASSWORD_CREATE', false), FILTER_VALIDATE_BOOLEAN),
                    'share' => filter_var(env('PASSBOLT_EMAIL_SEND_PASSWORD_SHARE', true), FILTER_VALIDATE_BOOLEAN),
                    'update' => filter_var(env('PASSBOLT_EMAIL_SEND_PASSWORD_UPDATE', true), FILTER_VALIDATE_BOOLEAN),
                    'delete' => filter_var(env('PASSBOLT_EMAIL_SEND_PASSWORD_DELETE', true), FILTER_VALIDATE_BOOLEAN),
                ],
                'user' => [
                    // WARNING: disabling PASSBOLT_EMAIL_SEND_USER_CREATE and PASSBOLT_EMAIL_SEND_USER_RECOVER will prevent user from signing up.
                    'create' => filter_var(env('PASSBOLT_EMAIL_SEND_USER_CREATE', true), FILTER_VALIDATE_BOOLEAN),
                    'recover' => filter_var(env('PASSBOLT_EMAIL_SEND_USER_RECOVER', true), FILTER_VALIDATE_BOOLEAN),
                    'recoverComplete' => filter_var(env('PASSBOLT_EMAIL_SEND_USER_RECOVER_COMPLETE', true), FILTER_VALIDATE_BOOLEAN),
                ],
                'admin' => [
                    'user' => [
                        'setup' => [
                            'completed' => filter_var(env('PASSBOLT_EMAIL_SEND_ADMIN_USER_SETUP_COMPLETED', true), FILTER_VALIDATE_BOOLEAN),
                        ],
                        'recover' => [
                            'abort' => filter_var(env('PASSBOLT_EMAIL_SEND_ADMIN_USER_RECOVER_ABORT', true), FILTER_VALIDATE_BOOLEAN),
                            'complete' => filter_var(env('PASSBOLT_EMAIL_SEND_ADMIN_USER_RECOVER_COMPLETE', true), FILTER_VALIDATE_BOOLEAN),
                        ],
                        'register' => [
                            'complete' => filter_var(env('PASSBOLT_EMAIL_SEND_ADMIN_USER_REGISTER_COMPLETE', true), FILTER_VALIDATE_BOOLEAN),
                        ],
                    ]
                ],
                'group' => [
                    // Notify all members that a group was deleted.
                    'delete' => filter_var(env('PASSBOLT_EMAIL_SEND_GROUP_DELETE', true), FILTER_VALIDATE_BOOLEAN),
                    'user' => [ // notify user group membership changes.
                        'add' => filter_var(env('PASSBOLT_EMAIL_SEND_GROUP_USER_ADD', true), FILTER_VALIDATE_BOOLEAN),
                        'delete' => filter_var(env('PASSBOLT_EMAIL_SEND_GROUP_USER_DELETE', true), FILTER_VALIDATE_BOOLEAN),
                        'update' => filter_var(env('PASSBOLT_EMAIL_SEND_GROUP_USER_UPDATE', true), FILTER_VALIDATE_BOOLEAN),
                    ],
                    'manager' => [
                        // Notify managers when group membership changes.
                        'update' => filter_var(env('PASSBOLT_EMAIL_SEND_GROUP_MANAGER_UPDATE', true), FILTER_VALIDATE_BOOLEAN),
                    ]
                ],
                'folder' => [
                    'create' => filter_var(env('PASSBOLT_EMAIL_SEND_FOLDER_CREATE', false), FILTER_VALIDATE_BOOLEAN),
                    'update' => filter_var(env('PASSBOLT_EMAIL_SEND_FOLDER_UPDATE', true), FILTER_VALIDATE_BOOLEAN),
                    'delete' => filter_var(env('PASSBOLT_EMAIL_SEND_FOLDER_DELETE', true), FILTER_VALIDATE_BOOLEAN),
                    'share' => filter_var(env('PASSBOLT_EMAIL_SEND_FOLDER_SHARE', true), FILTER_VALIDATE_BOOLEAN),
                ],
                // PRO EDITION ONLY
                'accountRecovery' => [
                    'request' => [
                        'user' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_REQUEST_USER', true), FILTER_VALIDATE_BOOLEAN),
                        'admin' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_REQUEST_ADMIN', true), FILTER_VALIDATE_BOOLEAN),
                        'guessing' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_REQUEST_GUESSING', true), FILTER_VALIDATE_BOOLEAN),
                    ],
                    'response' => [
                        'user' => [
                            'approved' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_RESPONSE_USER_APPROVED', true), FILTER_VALIDATE_BOOLEAN),
                            'rejected' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_RESPONSE_USER_REJECTED', true), FILTER_VALIDATE_BOOLEAN),
                        ],
                        'created' => [
                            'admin' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_RESPONSE_CREATED_ADMIN', true), FILTER_VALIDATE_BOOLEAN),
                            'allAdmins' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_RESPONSE_CREATED_ALL_ADMINS', true), FILTER_VALIDATE_BOOLEAN),
                        ],
                    ],
                    'policy' => [
                        'update' => filter_var(env('PASSBOLT_EMAIL_SEND_ACCOUNT_RECOVERY_POLICY_UPDATE', true), FILTER_VALIDATE_BOOLEAN),
                    ],
                ],
            ]
        ],

        // build | options : development or production.
        // development will load the non compiled version.
        // production will load the compiled passbolt.js file.
        'js' => [
            'build' => env('PASSBOLT_JS_BUILD', 'production')
        ],

        // Html meta information.
        'meta' => [
            'title' => env('PASSBOLT_META_TITLE', 'Passbolt'),
            'description' => env('PASSBOLT_META_DESCRIPTION', 'Open source password manager for teams'),
            // Do you want search engine robots to index your site.
            // Default is set to false.
            'robots' => env('PASSBOLT_META_ROBOTS', 'noindex, nofollow')
        ],

        // GPG Configuration.
        'gpg' => [
            // Tell passbolt which OpenPGP backend to use
            // Default is PHP-GNUPG with some help from OpenPGP-PHP
            'backend' => env('PASSBOLT_GPG_BACKEND', 'gnupg'),

            // Tell passbolt where to find the GnuPG keyring.
            // If putenv is set to false, gnupg will use the default path ~/.gnupg.
            // For example :
            // - Apache on Centos it would be in '/usr/share/httpd/.gnupg'
            // - Apache on Debian it would be in '/var/www/.gnupg'
            // - Nginx on Centos it would be in '/var/lib/nginx/.gnupg'
            // - etc.
            'keyring' => getenv("HOME") . DS . '.gnupg',

            // Replace GNUPGHOME with above value even if it is set.
            'putenv' => false,

            // Main server key.
            'serverKey' => [
                // Server public / private key location and fingerprint.
                'fingerprint' => env('PASSBOLT_GPG_SERVER_KEY_FINGERPRINT', null),
                'public' => env('PASSBOLT_GPG_SERVER_KEY_PUBLIC', CONFIG . 'gpg' . DS . 'serverkey.asc'),
                'private' => env('PASSBOLT_GPG_SERVER_KEY_PRIVATE', CONFIG . 'gpg' . DS . 'serverkey_private.asc'),

                // PHP Gnupg module currently does not support passphrase, please leave blank.
                'passphrase' => ''
            ],
            'experimental' => [
                'encryptValidate' => filter_var(env('PASSBOLT_GPG_EXTRA_ENCRYPT_VALIDATE', true), FILTER_VALIDATE_BOOLEAN)
            ]
        ],

        // Healthcheck
        'healthcheck' => [
            'error' => filter_var(env('PASSBOLT_HEALTHCHECK_ERROR', false), FILTER_VALIDATE_BOOLEAN)
        ],

        // Legal
        'legal' => [
            'privacy_policy' => [
                'url' => env('PASSBOLT_LEGAL_PRIVACYPOLICYURL', '')
            ],
            'terms' => [
                'url' => env('PASSBOLT_LEGAL_TERMSURL', 'https://www.passbolt.com/terms')
            ]
        ],

        // Which plugins are enabled
        'plugins' => [
            'export' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_EXPORT_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'import' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_IMPORT_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'previewPassword' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_PREVIEW_PASSWORD_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'resourceTypes' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_RESOURCE_TYPES_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'mobile' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_MOBILE_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'jwtAuthentication' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_JWT_AUTHENTICATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'accountRecoveryRequestHelp' => [
                // Feature flag to allow client to tune behavior for backward compatibility
                // e.g. updated recovery process allows for admin email notification with "lost-passphrase" option
                // @deprecated when v3.5 is dropped - Ref. PB-15046
                'enabled' => true,
                'settingsVisibility' => [
                    'whiteListPublic' => [
                        'enabled',
                    ],
                ],
            ],
            'accountRecovery' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_ACCOUNT_RECOVERY_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'smtpSettings' => [
                // A typo is here covered for backward compatibility
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_SMTP_SETTINGS_ENABLED', env('PASSBOLT_PLUGINS_SMTP_SETTINGS', true)), FILTER_VALIDATE_BOOLEAN)
            ],
            'selfRegistration' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_SELF_REGISTRATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'sso' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_SSO_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'providers' => [
                    SsoSetting::PROVIDER_AZURE => filter_var(
                        env('PASSBOLT_PLUGINS_SSO_PROVIDER_AZURE_ENABLED', true),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                ],
            ],
            'mfaPolicies' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_MFA_POLICIES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            ],
            'ssoRecover' => [
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_SSO_RECOVER_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ],
        ],

        // Activate specific entry points for selenium testing.
        // true will render your installation insecure.
        'selenium' => [
            'active' => filter_var(env('PASSBOLT_SELENIUM_ACTIVE', false), FILTER_VALIDATE_BOOLEAN),
            'sso' => [
                'active' => filter_var(env('PASSBOLT_SELENIUM_SSO_ACTIVE', false)),
                'azure' => [
                    'url' => env('PASSBOLT_SELENIUM_SSO_AZURE_URL', 'https://login.microsoftonline.com'),
                    'tenantId' => env('PASSBOLT_SELENIUM_SSO_AZURE_TENANT_ID', ''),
                    'clientId' => env('PASSBOLT_SELENIUM_SSO_AZURE_CLIENT_ID', ''),
                    'secretId' => env('PASSBOLT_SELENIUM_SSO_AZURE_SECRET_ID', ''),
                    'secretExpiry' => env('PASSBOLT_SELENIUM_SSO_AZURE_SECRET_EXPIRY', '')
                ],
                'google' => [
                    'clientId' => env('PASSBOLT_SELENIUM_SSO_GOOGLE_CLIENT_ID', ''),
                    'secretId' => env('PASSBOLT_SELENIUM_SSO_GOOGLE_SECRET_ID', ''),
                ],
            ],
        ],

        // Security.
        'security' => [
            'cookies' => [
                // force cookie secure flag even if request is not https
                'secure' => filter_var(env('PASSBOLT_SECURITY_COOKIE_SECURE', true), FILTER_VALIDATE_BOOLEAN)
            ],
            'setHeaders' => filter_var(env('PASSBOLT_SECURITY_SET_HEADERS', true), FILTER_VALIDATE_BOOLEAN),
            'csrfProtection' => [
                'active' => true,
                'unlockedActions' => [
                    'AuthLogin' => ['loginPost'],
                    'RecoverComplete' => ['complete'],
                    'SetupComplete' => ['complete'],
                    'TransfersUpdate' => ['updateNoSession'],
                ]
            ],
            'csp' => env('PASSBOLT_SECURITY_CSP', true),
            // enables the storage and display of the user agent (user's browser and hardware related information)
            'userAgent' => filter_var(env('PASSBOLT_SECURITY_USER_AGENT', true), FILTER_VALIDATE_BOOLEAN),
            // enables the storage and display if the user IP address
            'userIp' => filter_var(env('PASSBOLT_SECURITY_USER_IP', true), FILTER_VALIDATE_BOOLEAN),
            'smtpSettings' => [
                'endpointsDisabled' => filter_var(env('PASSBOLT_SECURITY_SMTP_SETTINGS_ENDPOINTS_DISABLED', false), FILTER_VALIDATE_BOOLEAN)
            ],
            // Enables trusting of HTTP_X headers set by most load balancers.
            // Only set to true if your instance runs behind load balancers/proxies that you control.
            'proxies' => [
                'active' => filter_var(env('PASSBOLT_SECURITY_PROXIES_ACTIVE', false), FILTER_VALIDATE_BOOLEAN),
                // If your instance is behind multiple proxies, redefine the list of IP addresses of proxies in your control in passbolt.php
                'trustedProxies' => [],
            ],
            'mfa' => [
                'duoVerifySubscriber' => filter_var(env('PASSBOLT_SECURITY_MFA_DUO_VERIFY_SUBSCRIBER', false), FILTER_VALIDATE_BOOLEAN)
            ],
        ],

        // Should the app be SSL / HTTPS only.
        // false will render your installation insecure.
        'ssl' => [
            'force' => filter_var(env('PASSBOLT_SSL_FORCE', true), FILTER_VALIDATE_BOOLEAN)
        ],
        //ObfuscateFields placeholder
        'obfuscateFields' => [
            'placeholder' => env('PASSBOLT_OBFUSCATE_FIELDS_PLACEHOLDER', \App\Controller\Component\ObfuscateFieldsComponent::FIELD_PLACEHOLDER),
        ]
    ],
    // Override the Cake ExceptionRenderer.
    'Error' => [
        'exceptionRenderer' => 'App\Error\AppExceptionRenderer',
    ]
];
