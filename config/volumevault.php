<?php

return [
    'host_path_allowlist' => array_values(array_filter(array_map(
        fn (string $path): string => trim($path),
        explode(',', env('VOLUMEVAULT_HOST_PATH_ALLOWLIST', ''))
    ))),

    'update_check' => [
        'enabled' => (bool) env('VOLUMEVAULT_UPDATE_CHECK_ENABLED', true),
        'cache_ttl_seconds' => (int) env('VOLUMEVAULT_UPDATE_CHECK_CACHE_TTL', 43200),
        'github_api_url' => env('VOLUMEVAULT_UPDATE_CHECK_URL', 'https://api.github.com/repos/Darkdragon14/VolumeVault/releases/latest'),
    ],
];
