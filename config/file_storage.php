<?php
use Burzum\FileStorage\Storage\StorageUtils;
use Burzum\FileStorage\Storage\StorageManager;
use Burzum\FileStorage\Event\ImageProcessingListener;
use Burzum\FileStorage\Event\LocalFileStorageListener;
use Cake\Core\Configure;
use Cake\Event\EventManager;

// Image versions configuration.
Configure::write('FileStorage', [
    // File storage adapter (Local or S3Image);
    'adapter' => 'S3Image',
    // Configure the `basePath` for the Local adapter, not needed when not using it
    'basePath' => APP . 'FileStorage' . DS,
    'imageDefaults' => [
        'Avatar' => [
            'medium' =>  'img' . DS . 'avatar' . DS . 'user_medium.png',
            'small' =>  'img' . DS . 'avatar' . DS . 'user.png',
        ]
    ],
    // Configure image versions on a per model base
    'imageSizes' => [
        'Avatar' => [
            'medium' => [
                'thumbnail' => [
                    'mode' => 'outbound',
                    'width' => 200,
                    'height' => 200
                ],
            ],
            'small' => [
                'thumbnail' => [
                    'mode' => 'outbound',
                    'width' => 80,
                    'height' => 80
                ],
                'crop' => [
                    'width' => 80,
                    'height' => 80
                ],
            ],
        ]
    ]
]);

$listener = new LocalFileStorageListener();
EventManager::instance()->on($listener);

// For automated image processing you'll have to attach this listener as well
$listener = new ImageProcessingListener();
EventManager::instance()->on($listener);

StorageUtils::generateHashes();

// Image storage paths.
Configure::write('ImageStorage.basePath', WWW_ROOT . 'img' . DS . 'public');
Configure::write('ImageStorage.publicPath', 'img' . DS . 'public');

if (Configure::read('FileStorage.adapter') === 'Local') {
    StorageManager::setConfig('Local', [
        'adapterOptions' => [
            Configure::read('ImageStorage.basePath'),
            true,
        ],
        'adapterClass' => '\Gaufrette\Adapter\Local',
        'class' => '\Gaufrette\Filesystem'
    ]);
} elseif (Configure::read('FileStorage.adapter') === 'S3Image') {
    $S3Client = \Aws\S3\S3Client::factory([
        'credentials' => array(
            // AWS key id
            'key' => 'aws-key',
            // AWS key secret
            'secret' => 'aws-secret'
        ),
        'region' => 'us-west-2',
        'version' => 'latest',
    ]);

    StorageManager::setConfig('S3Image', array(
            'adapterOptions' => array(
                $S3Client,
                'passbolt-avatars',
                array(
                ),
                true
            ),
            'adapterClass' => '\Gaufrette\Adapter\AwsS3',
            'class' => '\Gaufrette\Filesystem'
        )
    );
}