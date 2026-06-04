<?php

return [
    'advanced_alerting' => [
        'title' => 'Advanced alerting',
        'description' => 'VolumeVault can monitor backup jobs for stale backups, repeated failures, long-running error states, and unusual archive sizes.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Sneltoetsen',
        'description' => 'Gebruik op desktop Ctrl+K voor snelle navigatie, g-sneltoetsen voor weergaven en / om zoeken in lijsten te focussen.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Update-overzichten in de app',
        'description' => 'VolumeVault kan gebruikers nu tonen wat er is gewijzigd na een applicatie-update.',
    ],
    'available_update_checks' => [
        'title' => 'Controle op beschikbare updates',
        'description' => 'VolumeVault kan nu aangeven wanneer een nieuwere GitHub-release beschikbaar is.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Verwijderen vanaf taakdetail',
        'description' => 'Back-uptaken kunnen nu direct vanaf hun detailpagina worden verwijderd.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Meldingskanalen per taak',
        'description' => 'Back-uptaken kunnen nu kiezen welke actieve meldingskanalen hun resultaten ontvangen.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Migratie van standaardmeldingen',
        'description' => 'Deze release voegt meldingsinstellingen toe aan back-uptaken en standaardkanaaltracking aan meldingskanalen.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Hostpad-back-upbronnen',
        'description' => 'Admins kunnen geselecteerde mappen van de Docker-host back-uppen naast Docker-volumes.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Veiligheidscontroles voor hostpaden',
        'description' => 'Hostpaden worden alleen-lezen gekoppeld en kunnen worden beperkt met VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Back-updekking per stack',
        'description' => 'Docker-volumes worden gegroepeerd per Compose- of Swarm-stack met back-updekkingsstatussen.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Metadata van back-uparchief',
        'description' => 'Geslaagde runs kunnen nu archiefsleutels en groottes tonen wanneer bestemmingsmetadata beschikbaar is.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Ondersteuning voor vertrouwde proxies',
        'description' => "VolumeVault kan geconfigureerde reverse proxies vertrouwen zodat gegenereerde URL's het publieke HTTPS-schema gebruiken.",
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Schonere Docker-volume synchronisatie',
        'description' => 'Synchronisatie verwijdert nu verouderde ontbrekende volumerecords die niet langer door back-uptaken worden gebruikt.',
    ],
    'list_search_and_filters' => [
        'title' => 'Zoeken en filters in lijsten',
        'description' => 'Volumes en back-uptaken kregen zoeken, filters en een doorzoekbare volumeselector.',
    ],
    'php_85_container_runtime' => [
        'title' => 'PHP 8.5 container-runtime',
        'description' => 'De container is overgezet naar de ServerSideUp PHP 8.5 runtime met beheerde queue- en schedulerdiensten.',
    ],
    'first_stable_release' => [
        'title' => 'Eerste stabiele release',
        'description' => 'VolumeVault werd gelanceerd met geplande back-ups, veilige restores, versleutelde bestemmingen, meldingen, gebruikers, API-tokens en installatiesaves.',
    ],
];
