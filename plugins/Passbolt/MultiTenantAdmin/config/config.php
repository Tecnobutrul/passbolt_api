<?php
return [
    'passbolt' => [
        'plugins' => [
            'multiTenantAdmin' => [
                'security' => [
                    'csrfProtection' => [
                        'unlockedActions' => [
                            'Organizations' => ['add'],
                        ]
                    ]
                ],
            ]
        ]
    ]
];
