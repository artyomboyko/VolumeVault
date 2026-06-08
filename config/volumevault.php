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

    'run_logs' => [
        // Maximum size (in bytes) kept in a run's `logs` column. When exceeded,
        // the oldest output is dropped so the most recent lines (errors usually
        // surface last) are preserved. Set to 0 to disable the cap.
        'max_bytes' => (int) env('VOLUMEVAULT_RUN_LOG_MAX_BYTES', 262144),
    ],

    'alerts' => [
        'enabled' => (bool) env('VOLUMEVAULT_ALERTS_ENABLED', true),
        'defaults' => [
            'check_interval_minutes' => 60,
            'cooldown_minutes' => 1440,
            'reminder_enabled' => false,
            'backup_too_old_days' => 7,
            'job_never_succeeded_min_runs' => 3,
            'job_in_error_days' => 3,
            'backup_size_out_of_range_min_bytes' => 1024,
            'backup_size_out_of_range_max_bytes' => 10737418240,
        ],
    ],
];
