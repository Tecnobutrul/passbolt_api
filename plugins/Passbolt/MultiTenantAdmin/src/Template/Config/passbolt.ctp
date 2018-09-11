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
    'EmailTransport' => [
        'default' => [
            'host' => '<?= $config['email']['host'] ?>',
            'port' => <?= $config['email']['port'] ?>,
            'username' => '<?= $config['email']['username'] ?>',
            'password' => '<?= $config['email']['password'] ?>',
            'tls' => <?= $config['email']['tls'] ? 'true' : 'null' ?>,
        ],
    ],
    'Email' => [
        'default' => [
            'from' => ['<?= $config['email']['sender_email'] ?>' => '<?= $config['email']['sender_name'] ?>'],
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
