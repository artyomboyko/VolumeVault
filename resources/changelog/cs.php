<?php

return [
    'advanced_alerting' => [
        'title' => 'Advanced alerting',
        'description' => 'VolumeVault can monitor backup jobs for stale backups, repeated failures, long-running error states, and unusual archive sizes.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Upozorneni na limit uloziste cile',
        'description' => 'Cile zaloh mohou nyni nastavit absolutni varovne a kriticke prahy uloziste s vlastnimi notifikacnimi kanaly.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Vylepsena mobilni navigace',
        'description' => 'Mobilni hlavicka ted pouziva kompaktni tlacitko menu a strukturovany navigacni panel misto skladani vsech odkazu v hlavicce.',
    ],
    'alert_rule_pause_and_size_fixes' => [
        'title' => 'Alert rule fixes',
        'description' => 'Clearing the maximum backup size is accepted again, and paused jobs no longer keep stale or never-succeeded alerts active.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Klavesove zkratky',
        'description' => 'Na desktopu pouzijte Ctrl+K pro rychlou navigaci, zkratky s predponou g pro zobrazeni a / pro zamereni hledani v seznamech.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Souhrny aktualizaci v aplikaci',
        'description' => 'VolumeVault ted muze uzivatelum ukazat, co se po aktualizaci aplikace zmenilo.',
    ],
    'available_update_checks' => [
        'title' => 'Kontroly dostupnych aktualizaci',
        'description' => 'VolumeVault ted muze upozornit, kdyz je dostupne novejsi vydani na GitHubu.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Smazani ze stranky detailu ulohy',
        'description' => 'Zalozni ulohy lze ted smazat primo z jejich stranky detailu.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Kanaly oznameni pro jednotlive ulohy',
        'description' => 'Zalozni ulohy ted mohou vybrat, ktere aktivni kanaly oznameni dostanou jejich vysledky.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Migrace vychozich oznameni',
        'description' => 'Toto vydani pridava nastaveni oznameni k zaloznim uloham a sledovani vychoziho kanalu ke kanalum oznameni.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Zdroje zaloh z cest hostitele',
        'description' => 'Admini mohou zalohovat vybrane adresare z Docker hostitele vedle Docker svazku.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Bezpecnostni kontroly cest hostitele',
        'description' => 'Cesty hostitele jsou pripojeny pouze pro cteni a lze je omezit pomoci VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Pokryti zaloh podle stacku',
        'description' => 'Docker svazky jsou seskupeny podle Compose nebo Swarm stacku se stavy pokryti zaloh.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Metadata archivu zalohy',
        'description' => 'Uspesne behy ted mohou zobrazit klice a velikosti archivu, pokud jsou metadata cile dostupna.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Podpora duveryhodnych proxy',
        'description' => 'VolumeVault muze duverovat nastavenym reverznim proxy, aby generovane URL pouzivaly verejne HTTPS schema.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Cistsi synchronizace Docker svazku',
        'description' => 'Synchronizace ted odstranuje zastarale chybejici zaznamy svazku, ktere uz nejsou odkazovane zaloznimi ulohami.',
    ],
    'list_search_and_filters' => [
        'title' => 'Vyhledavani a filtry v seznamech',
        'description' => 'Svazky a zalozni ulohy ziskaly vyhledavani, filtry a prohledavatelny vyber svazku.',
    ],
    'php_85_container_runtime' => [
        'title' => 'Runtime kontejneru PHP 8.5',
        'description' => 'Kontejner presel na runtime ServerSideUp PHP 8.5 se spravovanou frontou a planovacem.',
    ],
    'first_stable_release' => [
        'title' => 'Prvni stabilni vydani',
        'description' => 'VolumeVault byl spusten s planovanymi zalohami, bezpecnymi obnovami, sifrovanymi cili, oznamenimi, uzivateli, API tokeny a instalacnimi zalohami.',
    ],
];
