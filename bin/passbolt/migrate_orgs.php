#!/usr/bin/env php -q
<?php

echo "     ____                  __          ____  \n";
echo "    / __ \____  _____ ____/ /_  ____  / / /_ \n";
echo "   / /_/ / __ `/ ___/ ___/ __ \/ __ \/ / __/ \n";
echo "  / ____/ /_/ (__  |__  ) /_/ / /_/ / / /    \n";
echo " /_/    \__,_/____/____/_.___/\____/_/\__/   \n";
echo "\n";
echo " Passbolt Cloud API Admin - Migrate organizations\n";
echo "\n";

$cloudApiAdminUrl = getenv('PASSBOLT_SCRIPT_CATALOG_URL');
$username = getenv('PASSBOLT_SCRIPT_CATALOG_AUTH_USERNAME');
$password = getenv('PASSBOLT_SCRIPT_CATALOG_AUTH_PASSWORD');
$authorizationHeader = 'Basic ' . base64_encode("$username:$password");
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: $authorizationHeader"
    ]
];
$context = stream_context_create($opts);
$url = "{$cloudApiAdminUrl}/multi_tenant/organizations.json";

$response = file_get_contents($url, false, $context);
if (!$response) {
    echo "Unable to connect to the cloud catalog service.\n";
    exit(1);
}

$json = \json_decode($response);

if (is_null($json) || !is_array($json->body)) {
    echo "The response of the cloud catalog service is not valid.\n";
    exit(1);
}

$organizations = $json->body;

if (empty($organizations)) {
    echo "No organization to migrate.\n";
    exit;
}

foreach ($organizations as $organization) {
    if (!preg_match('/^[a-z0-9]+[a-z0-9\-_]*[a-z0-9]+$/i', $organization->slug)) {
        echo "Cannot migrate the organization {$organization->slug}. The organization slug is not valid.\n";
        continue;
    }

    $slugParam = escapeshellarg($organization->slug);
    $command = __DIR__ . "/../cake passbolt migrate --org={$slugParam}";
    echo "Migrated: {$organization->slug}.\n";
    $output = shell_exec($command);
}
