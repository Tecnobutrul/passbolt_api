<?php
return [
    'passbolt' => [
        'plugins' => [
            'cloudSubscription' => [
                'version' => '1.0.0',
                'enabled' => filter_var(env('PASSBOLT_PLUGINS_CLOUDSUBSCRIPTION_ENABLED', true), FILTER_VALIDATE_BOOLEAN)
            ]
        ]
    ]
];
