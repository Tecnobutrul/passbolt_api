<?php
return [
    'passbolt' => [
        'plugins' => [
            'multiTenantAdmin' => [
                'auth' => [
                    'username' => env('PASSBOLT_PLUGINS_MULTITENANTADMIN_AUTH_USERNAME', '__USERNAME__'),
                    'password' => env('PASSBOLT_PLUGINS_MULTITENANTADMIN_AUTH_PASSWORD', '__PASSWORD__')
                ],
                'security' => [
                    'csrfProtection' => [
                        'unlockedActions' => [
                            'MultiTenantAdminRegisterFirstUser' => ['registerFirstUser'],
                            'MultiTenantAdminSubscriptionUpdate' => ['updateOrCreate']
                        ]
                    ]
                ]
            ]
        ]
    ]
];
