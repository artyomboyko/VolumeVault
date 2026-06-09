<?php

return [
    'host_path_allowlist_fail_closed' => [
        'title' => 'De hostpad-toelatingslijst is nu fail-closed',
        'description' => 'VOLUMEVAULT_HOST_PATH_ALLOWLIST weigert nu standaard: wanneer deze leeg is, worden back-upbronnen op basis van een hostpad en lokale bestemmingen geweigerd in plaats van elk pad toe te staan. Dezelfde lijst beschermt nu ook lokale bestemmingen, en paden worden tijdens runtime opnieuw gecontroleerd om het verwisselen van symbolische koppelingen te blokkeren. Bestaande installaties die op het vorige open standaardgedrag vertrouwden, moeten hun paden opgeven - voer "php artisan volumevault:host-path-allowlist:audit" uit voor de exacte in te stellen waarde.',
    ],
    'alert_check_isolation' => [
        'title' => 'Robuustere alertcontroles',
        'description' => 'Een alertregel die een fout veroorzaakt, verhindert niet langer dat de overige regels worden gecontroleerd. Elke regel wordt nu onafhankelijk geevalueerd en fouten worden gelogd, zodat een enkele falende controle je overige alerts niet meer stilletjes kan uitschakelen.',
    ],
    'restore_volume_cleanup' => [
        'title' => 'Schonere nieuwe pogingen na een mislukte restore',
        'description' => 'Wanneer een restore mislukt nadat het doelvolume is aangemaakt, verwijdert VolumeVault nu het gedeeltelijk aangemaakte volume zodat de volgende poging schoon start in plaats van te worden geblokkeerd door een "bestaat al"-fout.',
    ],
    'schedule_drift_prevention' => [
        'title' => 'Betrouwbaardere back-upplanning',
        'description' => 'Geplande back-ups slaan geen uitvoering meer over wanneer een worker achterloopt. De volgende uitvoering wordt nu verankerd aan het geplande tijdvak in plaats van aan de eindtijd van de vorige uitvoering, zodat een trage of vertraagde uitvoering de planning niet meer kan laten verschuiven.',
    ],
    'destination_usage_efficiency' => [
        'title' => 'Efficientere berekening van opslaggebruik van bestemming',
        'description' => 'Het opslaggebruik van back-upbestemmingen wordt nu berekend door de objecten te streamen in plaats van de hele lijst in het geheugen te laden, en SFTP-verbindingen worden daarna altijd gesloten. Bestemmingen met veel back-ups worden betrouwbaarder gemeten, zonder het geheugen uit te putten of verbindingen open te laten.',
    ],
    'run_log_integrity' => [
        'title' => 'Betrouwbaardere uitvoeringslogs',
        'description' => 'Logs van back-up- en restore-uitvoeringen worden nu atomair toegevoegd, zodat gelijktijdige updates - zoals een foutmelding en een melding over het herstarten van een container - elkaar niet meer overschrijven. De omvang is ook begrensd, waarbij de meest recente uitvoer behouden blijft in plaats van eindeloos te groeien.',
    ],
    'stale_run_reconciliation' => [
        'title' => 'Automatisch herstel van onderbroken runs',
        'description' => 'Back-up- en herstelruns die zijn onderbroken door een worker-crash, time-out of herstart worden nu automatisch als mislukt gemarkeerd in plaats van vast te blijven zitten, zodat geplande back-ups blijven draaien. Applicatiecontainers die voor een back-up zijn gestopt, worden ook automatisch herstart als een crash ze uitgeschakeld liet.',
    ],
    'advanced_alerting' => [
        'title' => 'Geavanceerde waarschuwingen',
        'description' => 'VolumeVault kan back-uptaken bewaken op verouderde back-ups, herhaalde mislukkingen, langdurige foutstatussen en ongebruikelijke archiefgroottes.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Opslaglimietwaarschuwingen',
        'description' => 'Backupbestemmingen kunnen nu absolute waarschuwings- en kritieke opslagdrempels met eigen meldingskanalen instellen.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Verbeterde mobiele navigatie',
        'description' => 'De mobiele kop gebruikt nu een compacte menuknop en een gestructureerd navigatiepaneel in plaats van alle links in de kop te stapelen.',
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
    'pagination_with_user_preference' => [
        'title' => 'Gepagineerde lijsten met per-pagina-voorkeur',
        'description' => 'Alle lijstweergaven ondersteunen nu paginering met configureerbaar aantal items per pagina (10, 20, 50, 100, of Alle). U kunt uw standaard instellen in de profielinstellingen.',
    ],
    'dark_pagination_menu' => [
        'title' => 'Donker paginatiemenu',
        'description' => 'De keuzelijst voor items per pagina behoudt nu een donker thema wanneer die wordt geopend, met beter contrast in gepagineerde lijstweergaven.',
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Vernieuwde primaire knoppen',
        'description' => 'Primaire actieknoppen delen nu in de hele applicatie dezelfde omlijnde blauwe stijl in zowel lichte als donkere modus.',
    ],
    'shareable_filter_urls' => [
        'title' => 'Deelbare filter-URLs',
        'description' => 'Lijstfilters voor Volumes, Stacks, Back-uptaken en Waarschuwingen worden nu weerspiegeld in de URL, zodat u gefilterde weergaven direct kunt kopieren en delen.',
    ],
    'safer_default_environment_settings' => [
        'title' => 'Veiligere standaard omgevingsinstellingen',
        'description' => '.env.example zet nieuwe deployments nu standaard op APP_ENV=production en APP_DEBUG=false. Er is ook uitleg toegevoegd voor SESSION_SECURE_COOKIE, zodat HTTPS-deployments veilige cookies kunnen inschakelen zonder per ongeluk alleen-HTTP-opstellingen te breken.',
    ],
];
