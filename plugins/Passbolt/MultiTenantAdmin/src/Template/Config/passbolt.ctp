return [
    'App' => [
        'fullBaseUrl' => '<?= $config['options']['full_base_url'] ?>',
    ],
    'Datasources' => [
        'default' => [
            'host' => '<?= $config['database']['host'] ?>',
            'port' => '<?= $config['database']['port'] ?>',
            'username' => '<?= $config['database']['username'] ?>',
            'password' => '<?= $config['database']['password'] ?>',
            'database' => '<?= $config['database']['database'] ?>',
        ],
    ],
    'passbolt' => [
        'meta' => [
            'title' => '<?= $config['meta']['title'] ?>',
        ],
        'gpg' => [
            'keyring' => '<?= $config['gpg']['keyring'] ?>',
            'putenv' => true,
            'serverKey' => [
                'fingerprint' => '<?= $config['gpg']['fingerprint'] ?>',
                'public' => '<?= $config['gpg']['public'] ?>',
                'private' => '<?= $config['gpg']['private'] ?>',
            ],
        ],
        'registration' => [
            'public' => <?= $config['options']['public_registration'] ? 'true' : 'false' ?>,
        ],
        'ssl' => [
            'force' => <?= $config['options']['force_ssl'] ? 'true' : 'false' ?>,
        ]
    ],
];
