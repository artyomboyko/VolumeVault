<?php

return [
    'ssrf_destination_guard' => [
        'title' => 'Backup-Ziele mit privater IP sind jetzt geschuetzt (SSRF)',
        'description' => 'VolumeVault weigert sich jetzt standardmaessig, eine Verbindung zu einem Backup-Ziel herzustellen, dessen Host zu einer privaten, Loopback- oder Link-Local-Adresse aufgeloest wird (einschliesslich des Cloud-Metadaten-Endpunkts 169.254.169.254). Dies betrifft nur Ziele mit privater IP, etwa ein NAS im LAN oder ein selbst gehostetes S3/MinIO - Cloud-Ziele ueber eine oeffentliche URL sind nicht betroffen. Geplante Backups laufen weiter, aber der Zieltest, die Wiederherstellung (Auflistung und Download) und die Speicherkontingent-Warnung sind blockiert, bis Sie den Bereich des Ziels in VOLUMEVAULT_SSRF_ALLOWED_IPS eintragen (kommagetrennte CIDRs, z. B. 192.168.1.0/24). Benachrichtigungskanaele werden nicht geschuetzt.',
    ],
    'host_path_allowlist_fail_closed' => [
        'title' => 'Die Hostpfad-Zulassungsliste ist jetzt fail-closed',
        'description' => 'VOLUMEVAULT_HOST_PATH_ALLOWLIST verweigert jetzt standardmaessig: wenn sie leer ist, werden Hostpfad-Sicherungsquellen und lokale Ziele abgelehnt, statt jeden Pfad zuzulassen. Dieselbe Liste schuetzt nun auch lokale Ziele, und Pfade werden zur Laufzeit erneut geprueft, um den Austausch symbolischer Links zu blockieren. Bestehende Installationen, die sich auf das bisherige offene Standardverhalten verlassen haben, muessen ihre Pfade auflisten - fuehren Sie "php artisan volumevault:host-path-allowlist:audit" aus, um den genau einzutragenden Wert zu erhalten.',
    ],
    'auth_rate_limiting' => [
        'title' => 'Ratenbegrenzte Anmeldung und Passwortruecksetzung',
        'description' => 'Anmelde- und Passwortruecksetzungsanfragen sind jetzt auf 5 Versuche pro Minute begrenzt, was Brute-Force-Angriffe auf das Administratorpasswort verlangsamt. Beim Ueberschreiten des Limits wird eine voruebergehende "zu viele Anfragen"-Antwort zurueckgegeben, die sich nach einer Minute zuruecksetzt.',
    ],
    'restore_input_hardening' => [
        'title' => 'Strengere Pruefung von Wiederherstellungs- und Sicherungseingaben',
        'description' => 'Die fuer eine Wiederherstellung ausgewaehlte Sicherung muss jetzt mit der Auflistung des Ziels uebereinstimmen, wodurch Pfaddurchquerungs-Schluessel wie "../../etc/passwd" blockiert werden. Docker-Volumenamen sind auf sichere Zeichen beschraenkt, und die Wiederherstellungsentpackung wird eingegrenzt, sodass ein gefaelschtes Archiv nicht ausserhalb des Zielvolumes schreiben kann.',
    ],
    'sftp_host_key_pinning' => [
        'title' => 'Anpinnen des SSH-Hostschluessels fuer SFTP-Ziele',
        'description' => 'SSH/SFTP-Ziele koennen jetzt den Hostschluessel des Servers anpinnen, um Man-in-the-Middle-Angriffe zu blockieren. Verwenden Sie die Schaltflaeche "Schluessel vom Server abrufen" - oder den neuen Endpunkt POST /api/v1/destinations/host-key -, um dem praesentierten Schluessel zu vertrauen, oder fuegen Sie einen Hostschluessel oder SHA256-Fingerabdruck ein. Der Schluessel wird vor dem Senden von Anmeldedaten geprueft, fuer die von VolumeVault durchgefuehrten SFTP-Vorgaenge (Test, Auflistung, Wiederherstellung). Leer lassen behaelt das bisherige Verhalten bei.',
    ],
    'api_token_expiration' => [
        'title' => 'API-Tokens laufen jetzt standardmaessig ab',
        'description' => 'API-Tokens laufen jetzt standardmaessig 60 Tage nach der Erstellung ab, was die Auswirkungen eines geleakten Tokens begrenzt. Bestehende, aeltere Tokens funktionieren nach dem Upgrade nicht mehr und muessen neu erstellt werden. Setzen Sie SANCTUM_TOKEN_EXPIRATION (in Minuten), um den Zeitraum zu aendern, oder null, um nicht ablaufende Tokens zu behalten. Ein Ablauf pro Token kann diesen Zeitraum nur verkuerzen, niemals verlaengern.',
    ],
    'alert_check_isolation' => [
        'title' => 'Robustere Alarmpruefungen',
        'description' => 'Eine Alarmregel, die einen Fehler ausloest, verhindert nicht mehr die Pruefung der uebrigen Regeln. Jede Regel wird jetzt unabhaengig ausgewertet und Fehler werden protokolliert, sodass eine fehlerhafte Pruefung die anderen Alarme nicht mehr stillschweigend deaktivieren kann.',
    ],
    'restore_volume_cleanup' => [
        'title' => 'Sauberere Wiederholungen nach fehlgeschlagener Wiederherstellung',
        'description' => 'Wenn eine Wiederherstellung nach dem Anlegen des Zielvolumes fehlschlaegt, entfernt VolumeVault jetzt das teilweise erstellte Volume, damit der naechste Versuch sauber startet und nicht durch einen "existiert bereits"-Fehler blockiert wird.',
    ],
    'schedule_drift_prevention' => [
        'title' => 'Zuverlaessigere Backup-Planung',
        'description' => 'Geplante Backups ueberspringen keinen Durchlauf mehr, wenn ein Worker in Verzug geraet. Der naechste Lauf wird jetzt am geplanten Zeitfenster verankert statt an der Endzeit des vorherigen Laufs, sodass ein langsamer oder verspaeteter Lauf den Zeitplan nicht mehr verschieben kann.',
    ],
    'destination_usage_efficiency' => [
        'title' => 'Effizientere Ermittlung der Zielspeichernutzung',
        'description' => 'Die Speichernutzung von Backup-Zielen wird jetzt per Streaming durch die Objekte ermittelt, statt die gesamte Liste in den Speicher zu laden, und SFTP-Verbindungen werden anschliessend immer geschlossen. Ziele mit vielen Backups werden zuverlaessiger gemessen, ohne den Speicher zu erschoepfen oder Verbindungen offen zu lassen.',
    ],
    'run_log_integrity' => [
        'title' => 'Zuverlaessigere Laufprotokolle',
        'description' => 'Protokolle von Backup- und Wiederherstellungslaeufen werden jetzt atomar angehaengt, sodass gleichzeitige Aktualisierungen - etwa eine Fehlermeldung und ein Hinweis auf den Container-Neustart - sich nicht mehr gegenseitig ueberschreiben. Die Protokollgroesse ist zudem begrenzt und behaelt die neueste Ausgabe, statt unbegrenzt zu wachsen.',
    ],
    'stale_run_reconciliation' => [
        'title' => 'Automatische Wiederherstellung unterbrochener Laeufe',
        'description' => 'Backup- und Wiederherstellungslaeufe, die durch einen Worker-Absturz, ein Timeout oder einen Neustart unterbrochen wurden, werden jetzt automatisch als fehlgeschlagen markiert, statt haengen zu bleiben, sodass geplante Backups weiterlaufen. Anwendungscontainer, die fuer ein Backup gestoppt wurden, werden ebenfalls automatisch neu gestartet, falls ein Absturz sie ausgeschaltet zurueckliess.',
    ],
    'advanced_alerting' => [
        'title' => 'Erweiterte Benachrichtigungen',
        'description' => 'VolumeVault kann Backup-Jobs auf veraltete Backups, wiederholte Fehler, lang anhaltende Fehlerzustaende und ungewoehnliche Archivgroessen ueberwachen.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Speicherlimit-Warnungen fuer Ziele',
        'description' => 'Backup-Ziele koennen jetzt absolute Warn- und kritische Speicherschwellen mit eigenen Benachrichtigungskanaelen festlegen.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Verbesserte mobile Navigation',
        'description' => 'Die mobile Kopfzeile nutzt jetzt eine kompakte Menu-Schaltflaeche und ein strukturiertes Navigationspanel, statt alle Links in der Kopfzeile zu stapeln.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Tastaturkuerzel',
        'description' => 'Auf dem Desktop nutzen Sie Ctrl+K fuer die Schnellnavigation, g-Kuerzel fuer Ansichten und / zum Fokussieren der Listensuche.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Update-Zusammenfassungen in der App',
        'description' => 'VolumeVault kann Benutzern jetzt anzeigen, was sich nach einem Anwendungsupdate geaendert hat.',
    ],
    'available_update_checks' => [
        'title' => 'Pruefung auf verfuegbare Updates',
        'description' => 'VolumeVault kann jetzt anzeigen, wenn ein neueres GitHub-Release verfuegbar ist.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Loeschen aus der Job-Detailseite',
        'description' => 'Backup-Jobs koennen jetzt direkt von ihrer Detailseite geloescht werden.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Benachrichtigungskanaele pro Job',
        'description' => 'Backup-Jobs koennen jetzt auswaehlen, welche aktiven Benachrichtigungskanaele ihre Ergebnisse erhalten.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Migration der Standardbenachrichtigungen',
        'description' => 'Dieses Release fuegt Backup-Jobs Benachrichtigungseinstellungen und Benachrichtigungskanaelen die Nachverfolgung des Standardkanals hinzu.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Host-Pfad-Backup-Quellen',
        'description' => 'Admins koennen ausgewaehlte Verzeichnisse vom Docker-Host zusaetzlich zu Docker-Volumes sichern.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Sicherheitskontrollen fuer Host-Pfade',
        'description' => 'Host-Pfade werden schreibgeschuetzt eingebunden und koennen mit VOLUMEVAULT_HOST_PATH_ALLOWLIST eingeschraenkt werden.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Stack-Backup-Abdeckung',
        'description' => 'Docker-Volumes werden nach Compose- oder Swarm-Stack mit Backup-Abdeckungsstatus gruppiert.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Backup-Archiv-Metadaten',
        'description' => 'Erfolgreiche Laeufe koennen jetzt Archivschluessel und Groessen anzeigen, wenn Zielmetadaten verfuegbar sind.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Unterstuetzung vertrauenswuerdiger Proxys',
        'description' => 'VolumeVault kann konfigurierten Reverse Proxys vertrauen, damit generierte URLs das oeffentliche HTTPS-Schema verwenden.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Sauberere Docker-Volume-Synchronisierung',
        'description' => 'Die Synchronisierung entfernt jetzt veraltete fehlende Volume-Eintraege, die von keinen Backup-Jobs mehr referenziert werden.',
    ],
    'list_search_and_filters' => [
        'title' => 'Listensuche und Filter',
        'description' => 'Volumes und Backup-Jobs haben Suche, Filter und einen durchsuchbaren Volume-Selektor erhalten.',
    ],
    'php_85_container_runtime' => [
        'title' => 'PHP 8.5 Container-Runtime',
        'description' => 'Der Container wurde auf die ServerSideUp PHP 8.5 Runtime mit ueberwachter Queue und Scheduler-Diensten umgestellt.',
    ],
    'first_stable_release' => [
        'title' => 'Erstes stabiles Release',
        'description' => 'VolumeVault startete mit geplanten Backups, sicheren Wiederherstellungen, verschluesselten Zielen, Benachrichtigungen, Benutzern, API-Tokens und Installationssicherungen.',
    ],
    'pagination_with_user_preference' => [
        'title' => 'Paginierte Listen mit Einstellung pro Seite',
        'description' => 'Alle Listenansichten unterstuetzen jetzt Paginierung mit konfigurierbaren Eintraegen pro Seite (10, 20, 50, 100 oder Alle). Sie koennen Ihren Standardwert in den Profileinstellungen festlegen.',
    ],
    'dark_pagination_menu' => [
        'title' => 'Dunkles Paginierungsmenue',
        'description' => 'Das Auswahlfeld fuer Eintraege pro Seite behaelt jetzt beim Oeffnen eine dunkle Darstellung bei und verbessert so den Kontrast in paginierten Listenansichten.',
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Aktualisierte Primaer-Schaltflaechen',
        'description' => 'Primaere Aktionsschaltflaechen verwenden jetzt in der gesamten Anwendung denselben blau umrandeten Stil in heller und dunkler Darstellung.',
    ],
    'shareable_filter_urls' => [
        'title' => 'Teilbare Filter-URLs',
        'description' => 'Filter in den Listen Volumes, Stacks, Backup-Jobs und Warnungen werden jetzt in der URL abgebildet, sodass Sie gefilterte Ansichten direkt kopieren und teilen koennen.',
    ],
    'safer_default_environment_settings' => [
        'title' => 'Sicherere Standard-Umgebungseinstellungen',
        'description' => 'Neue Deployments verwenden in der .env.example jetzt standardmaessig APP_ENV=production und APP_DEBUG=false. Ausserdem gibt es einen Hinweis zu SESSION_SECURE_COOKIE, damit HTTPS-Deployments sichere Cookies aktivieren koennen, ohne versehentlich reine HTTP-Setups auszusperren.',
    ],
];
