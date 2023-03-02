<?php

return [
    'passbolt' => [
        'plugins' => [
            'sso' => [
                'version' => '1.0.0',
                //'enabled' => true // see default.php
                'settingsVisibility' => [
                    'whiteListPublic' => [
                        'enabled',
                    ],
                ],
                'security' => [
                    // if supported by provider
                    // force authentication with SSO provider even if user is logged in
                    'prompt' => filter_var(env('PASSBOLT_PLUGINS_SSO_SECURITY_PROMPT', true), FILTER_VALIDATE_BOOLEAN),
                    // Disable CSRF protection on provider redirect
                    // CSRF protection is then handled via the state parameter
                    'csrfProtection' => [
                        'unlockedActions' => [
                            'SsoAzureStage2' => 'triage',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
