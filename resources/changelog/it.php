<?php

return [
    'advanced_alerting' => [
        'title' => 'Advanced alerting',
        'description' => 'VolumeVault can monitor backup jobs for stale backups, repeated failures, long-running error states, and unusual archive sizes.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Avvisi limite storage destinazione',
        'description' => 'Le destinazioni possono ora definire soglie assolute warning e critiche con canali di notifica dedicati.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Navigazione mobile migliorata',
        'description' => "L'intestazione mobile ora usa un pulsante menu compatto e un pannello di navigazione strutturato invece di impilare tutti i link nell'intestazione.",
    ],
    'keyboard_shortcuts' => [
        'title' => 'Scorciatoie da tastiera',
        'description' => 'Su desktop, usa Ctrl+K per la navigazione rapida, scorciatoie con prefisso g per le viste e / per focalizzare la ricerca nelle liste.',
    ],
    'in_app_update_summaries' => [
        'title' => "Riepiloghi aggiornamento nell'app",
        'description' => "VolumeVault ora puo mostrare agli utenti cosa e cambiato dopo un aggiornamento dell'applicazione.",
    ],
    'available_update_checks' => [
        'title' => 'Controlli aggiornamenti disponibili',
        'description' => 'VolumeVault ora puo indicare quando e disponibile una nuova versione su GitHub.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Eliminazione dal dettaglio processo',
        'description' => 'I processi backup ora possono essere eliminati direttamente dalla loro pagina dettaglio.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Canali di notifica per processo',
        'description' => 'I processi backup ora possono scegliere quali canali di notifica attivi ricevono i risultati.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Migrazione notifiche predefinite',
        'description' => 'Questa versione aggiunge impostazioni di notifica ai processi backup e tracciamento del canale predefinito ai canali di notifica.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Sorgenti backup percorso host',
        'description' => "Gli amministratori possono salvare directory selezionate dall'host Docker insieme ai volumi Docker.",
    ],
    'host_path_safety_controls' => [
        'title' => 'Controlli sicurezza percorso host',
        'description' => 'I percorsi host sono montati in sola lettura e possono essere limitati con VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Copertura backup per stack',
        'description' => 'I volumi Docker sono raggruppati per stack Compose o Swarm con stati di copertura backup.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Metadati archivio backup',
        'description' => 'Le esecuzioni riuscite ora possono mostrare chiavi e dimensioni degli archivi quando i metadati della destinazione sono disponibili.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Supporto proxy attendibili',
        'description' => 'VolumeVault puo considerare attendibili i reverse proxy configurati affinche gli URL generati usino lo schema HTTPS pubblico.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Sincronizzazione volumi Docker piu pulita',
        'description' => 'La sincronizzazione ora rimuove vecchi record di volumi mancanti che non sono piu referenziati da processi backup.',
    ],
    'list_search_and_filters' => [
        'title' => 'Ricerca e filtri nelle liste',
        'description' => 'Volumi e processi backup hanno ricevuto ricerca, filtri e un selettore volume ricercabile.',
    ],
    'php_85_container_runtime' => [
        'title' => 'Runtime container PHP 8.5',
        'description' => 'Il container e passato al runtime ServerSideUp PHP 8.5 con servizi coda e scheduler supervisionati.',
    ],
    'first_stable_release' => [
        'title' => 'Prima versione stabile',
        'description' => 'VolumeVault e stato lanciato con backup pianificati, ripristini sicuri, destinazioni cifrate, notifiche, utenti, token API e salvataggi installazione.',
    ],
    'pagination_with_user_preference' => [
        'title' => 'Liste paginate con preferenza per pagina',
        'description' => 'Tutte le viste elenco ora supportano la paginazione con elementi configurabili per pagina (10, 20, 50, 100, o Tutti). Puoi impostare il tuo predefinito nelle impostazioni del profilo.',
    ],
];
