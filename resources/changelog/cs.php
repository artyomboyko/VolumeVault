<?php

return [
    'alert_check_isolation' => [
        'title' => 'Odolnejsi kontroly upozorneni',
        'description' => 'Pravidlo upozorneni, ktere skonci chybou, jiz nebrani kontrole ostatnich pravidel. Kazde pravidlo se nyni vyhodnocuje samostatne a chyby se zaznamenavaji, takze jedna chybna kontrola jiz nemuze tise vypnout ostatni upozorneni.',
    ],
    'restore_volume_cleanup' => [
        'title' => 'Cistejsi opakovani po neuspesnem obnoveni',
        'description' => 'Kdyz obnoveni selze po vytvoreni ciloveho svazku, VolumeVault nyni castecne vytvoreny svazek odstrani, aby dalsi pokus zacal cisty a nebyl blokovan chybou "jiz existuje".',
    ],
    'schedule_drift_prevention' => [
        'title' => 'Spolehlivejsi planovani zaloh',
        'description' => 'Naplanovane zalohy jiz nevynechaji spusteni, kdyz se worker zpozdi. Dalsi spusteni se nyni ukotvi k planovanemu oknu misto k casu dokonceni predchoziho spusteni, takze pomale nebo opozdene spusteni jiz nemuze zpusobit posun rozvrhu.',
    ],
    'destination_usage_efficiency' => [
        'title' => 'Efektivnejsi vypocet vyuziti uloziste cile',
        'description' => 'Vyuziti uloziste cilu zaloh se nyni pocita prubeznym prochazenim objektu misto nacitani celeho seznamu do pameti a SFTP spojeni se po dokonceni vzdy uzavre. Cile s mnoha zalohami se meri spolehliveji, bez vycerpani pameti nebo ponechani otevrenych spojeni.',
    ],
    'run_log_integrity' => [
        'title' => 'Spolehlivejsi protokoly behu',
        'description' => 'Protokoly behu zaloh a obnoveni se nyni pripojuji atomicky, takze soubezne aktualizace - napriklad chybova zprava a upozorneni na restart kontejneru - se jiz vzajemne neprepisuji. Velikost protokolu je take omezena a zachovava nejnovejsi vystup misto neomezeneho rustu.',
    ],
    'stale_run_reconciliation' => [
        'title' => 'Automaticke obnoveni preruseny behu',
        'description' => 'Behy zalohovani a obnovy preruseny padem workeru, timeoutem nebo restartem jsou nyni automaticky oznaceny jako neuspesne, misto aby zustaly zaseknute, takze planovane zalohy bezi dal. Aplikacni kontejnery zastavene kvuli zaloze se take automaticky znovu spusti, pokud je pad nechal vypnute.',
    ],
    'advanced_alerting' => [
        'title' => 'Pokrocile upozornovani',
        'description' => 'VolumeVault muze sledovat zalozni ulohy a hlidat zastarale zalohy, opakovane selhani, dlouhotrvajici chybove stavy a neobvykle velikosti archivu.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Upozorneni na limit uloziste cile',
        'description' => 'Cile zaloh mohou nyni nastavit absolutni varovne a kriticke prahy uloziste s vlastnimi notifikacnimi kanaly.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Vylepsena mobilni navigace',
        'description' => 'Mobilni hlavicka ted pouziva kompaktni tlacitko menu a strukturovany navigacni panel misto skladani vsech odkazu v hlavicce.',
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
    'pagination_with_user_preference' => [
        'title' => 'Strankovane seznamy s preferencemi na stranku',
        'description' => 'Vsechny pohledy seznamu nyni podporuji strankovani s konfigurovatelnym poctem polozek na stranku (10, 20, 50, 100 nebo Vse). Vychozi hodnotu nastavite v nastaveni profilu.',
    ],
    'dark_pagination_menu' => [
        'title' => 'Tmave menu strankovani',
        'description' => 'Vyber poctu polozek na stranku si po otevreni nyni zachova tmavy vzhled a lepsi kontrast ve strankovanych seznamech.',
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Obnovena primarni tlacitka',
        'description' => 'Primarni akcni tlacitka ted sdileji stejny modre oramovany styl v cele aplikaci ve svetlem i tmavem rezimu.',
    ],
    'shareable_filter_urls' => [
        'title' => 'Sditelne URL s filtry',
        'description' => 'Filtry seznamu Svazku, Stacku, Zaloznich uloh a Upozorneni se nyni promitaji v URL, coz umoznuje primo kopirovat a sdilet filtrovane pohledy.',
    ],
    'safer_default_environment_settings' => [
        'title' => 'Bezpecnejsi vychozi nastaveni prostredi',
        'description' => '.env.example ted pro nova nasazeni standardne nastavuje APP_ENV=production a APP_DEBUG=false. Zaroven pridava pokyny pro SESSION_SECURE_COOKIE, aby bylo mozne u HTTPS nasazeni zapnout zabezpecene cookies bez nechteneho rozbiti ciste HTTP instalaci.',
    ],
];
