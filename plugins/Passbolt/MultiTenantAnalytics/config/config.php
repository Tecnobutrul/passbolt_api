<?php
return [
    'passbolt' => [
        'multiTenantAnalytics' => [
            'entryPoint' => [
                'url' => env('PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_URL', 'https://my-entry-point-url.com'),
                'username' => env('PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_USERNAME', 'username'),
                'password' => env('PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_PASSWORD', 'password'),
            ],
        ],
    ],
];
