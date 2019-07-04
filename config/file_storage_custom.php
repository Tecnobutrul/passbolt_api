<?php
use Cake\Core\Configure;
use Burzum\FileStorage\Storage\StorageUtils;
use Burzum\FileStorage\Storage\StorageManager;

// Gather bucket name from environment variable
$gcpImageBaseUrl = env('GOOGLE_IMAGE_BASE_URL', 'https://storage.googleapis.com');
$gcpImageBucketName = getenv('GOOGLE_IMAGE_STORAGE_BUCKET');
if ($gcpImageBucketName === false) {
    trigger_error('You must specify a GCP bucket to store images.', E_USER_ERROR);
}

// Get the credentials from environment variable
// Or use service-account.json in /config if present
if (env('GOOGLE_APPLICATION_CREDENTIALS') === null) {
    if (!file_exists(__DIR__ . DS . 'service-account.json')) {
        trigger_error('You must set cloud storage service account credentials.', E_USER_ERROR);
    } else {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . DS . 'service-account.json');
    }
}

// File storage and images
if (defined('PASSBOLT_ORG')) {
    Configure::write('ImageStorage.adapter', 'gcsBucket');
    Configure::write('ImageStorage.basePath', WWW_ROOT . 'img' . DS . 'public' . DS . PASSBOLT_ORG);
    Configure::write('ImageStorage.publicPath', $gcpImageBaseUrl . '/' . $gcpImageBucketName . '/' . PASSBOLT_ORG . '/');

    Configure::write('FileStorage', array(
        // Configure the `basePath` for the Local adapter, not needed when not using it
        'basePath' => APP . 'FileStorage' . DS . PASSBOLT_ORG,
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
    ));

    StorageUtils::generateHashes();
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();
    $client->addScope(\Google_Service_Storage::DEVSTORAGE_FULL_CONTROL);
    $service = new \Google_Service_Storage($client);

    StorageManager::config('gcsBucket', [
        'adapterOptions' => [
            $service, $gcpImageBucketName, array(
                'directory' => PASSBOLT_ORG,
                'acl' => 'public',
            ), true
        ],
        'adapterClass' => '\Gaufrette\Adapter\GoogleCloudStorage',
        'class' => '\Gaufrette\Filesystem'
    ]);
}