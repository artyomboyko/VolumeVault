<?php

return [
    'advanced_alerting' => [
        'title' => 'Advanced alerting',
        'description' => 'VolumeVault can monitor backup jobs for stale backups, repeated failures, long-running error states, and unusual archive sizes.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Destination storage limit alerts',
        'description' => 'Backup destinations can now define absolute warning and critical storage thresholds with dedicated alert notification channels.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Improved mobile navigation',
        'description' => 'The mobile header now uses a compact menu button and a structured navigation panel instead of stacking every link in the header.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Keyboard shortcuts',
        'description' => 'On desktop, use Ctrl+K for quick navigation, g-prefixed shortcuts for views, and / to focus list search.',
    ],
    'in_app_update_summaries' => [
        'title' => 'In-app update summaries',
        'description' => 'VolumeVault can now show users what changed after an application update.',
    ],
    'available_update_checks' => [
        'title' => 'Available update checks',
        'description' => 'VolumeVault can now indicate when a newer GitHub release is available.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Backup job detail deletion',
        'description' => 'Backup jobs can now be deleted directly from their detail page.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Per-job notification channels',
        'description' => 'Backup jobs can now choose which active notification channels receive their results.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Notification defaults migration',
        'description' => 'This release adds notification settings to backup jobs and default-channel tracking to notification channels.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Host path backup sources',
        'description' => 'Admins can back up selected directories from the Docker host alongside Docker volumes.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Host path safety controls',
        'description' => 'Host paths are mounted read-only and can be restricted with VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Stack backup coverage',
        'description' => 'Docker volumes are grouped by Compose or Swarm stack with backup coverage states.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Backup archive metadata',
        'description' => 'Successful runs can now show archive keys and sizes when destination metadata is available.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Trusted proxy support',
        'description' => 'VolumeVault can trust configured reverse proxies so generated URLs use the public HTTPS scheme.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Cleaner Docker volume sync',
        'description' => 'Sync now removes stale missing volume records that are no longer referenced by backup jobs.',
    ],
    'list_search_and_filters' => [
        'title' => 'List search and filters',
        'description' => 'Volumes and backup jobs gained search, filters, and a searchable volume selector.',
    ],
    'php_85_container_runtime' => [
        'title' => 'PHP 8.5 container runtime',
        'description' => 'The container moved to the ServerSideUp PHP 8.5 runtime with supervised queue and scheduler services.',
    ],
    'first_stable_release' => [
        'title' => 'First stable release',
        'description' => 'VolumeVault launched with scheduled backups, safe restores, encrypted destinations, notifications, users, API tokens, and installation saves.',
    ],
    'pagination_with_user_preference' => [
        'title' => 'Paginated lists with per-page preference',
        'description' => 'All list views now support pagination with configurable items per page (10, 20, 50, 100, or All). You can set your default in Profile settings.',
    ],
    'dark_pagination_menu' => [
        'title' => 'Dark pagination menu',
        'description' => 'The items-per-page dropdown now keeps a dark theme palette when its menu is open, improving contrast in paginated list views.',
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Refreshed primary buttons',
        'description' => 'Primary action buttons now share the same outlined sky style in both light and dark mode across the application.',
    ],
    'shareable_filter_urls' => [
        'title' => 'Shareable filter URLs',
        'description' => 'List filters on Volumes, Stacks, Backup Jobs, and Alerts are now reflected in the URL so you can copy and share filtered views directly.',
    ],
];
