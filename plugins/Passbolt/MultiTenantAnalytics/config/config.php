<?php
return [
    'passbolt' => [
        'multiTenantAnalytics' => [
            'entryPoint' => [
                'url' => env('PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_URL', 'https://europe-west1-passbolt-testing-78757.cloudfunctions.net/onOrgAnalyticsUpdate'),
                'username' => env('PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_USERNAME', 'username'),
                'password' => env('PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_PASSWORD', 'password'),
            ],
        ],
    ],
];
