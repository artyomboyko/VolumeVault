<?php

return [
    'restore_volume_cleanup' => [
        'title' => 'Tisztabb ujraprobalkozasok sikertelen visszaallitas utan',
        'description' => 'Ha egy visszaallitas a celkotet letrehozasa utan meghiusul, a VolumeVault mostantol torli a reszben letrehozott kotetet, igy a kovetkezo probalkozas tisztan indul, ahelyett hogy egy "mar letezik" hiba blokkolna.',
    ],
    'stale_run_reconciliation' => [
        'title' => 'Megszakitott futasok automatikus helyreallitasa',
        'description' => 'A worker osszeomlasa, idotullepes vagy ujrainditas miatt megszakitott mentesi es visszaallitasi futasok mostantol automatikusan sikertelenkent jelolodnek meg, ahelyett hogy elakadva maradnanak, igy az utemezett mentesek tovabb futnak. A mentes miatt leallitott alkalmazaskontenerek is automatikusan ujraindulnak, ha egy osszeomlas leallitva hagyta oket.',
    ],
    'advanced_alerting' => [
        'title' => 'Fejlett riasztas',
        'description' => 'A VolumeVault figyelheti a mentesi feladatokat elavult mentesek, ismetlodo hibak, hosszan tarto hibaallapotok es szokatlan archivum meretek szempontjabol.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Cel tarhelykorlat riasztasok',
        'description' => 'A mentesi celok most abszolut figyelmeztetesi es kritikus tarhelykuszoboket allithatnak be dedikalt ertesitesi csatornakkal.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Tovabbfejlesztett mobil navigacio',
        'description' => 'A mobil fejlec most kompakt menugombot es strukturalt navigacios panelt hasznal, ahelyett hogy minden hivatkozast a fejlecbe zsufolna.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Billentyuparancsok',
        'description' => 'Desktopon hasznalja a Ctrl+K-t gyors navigaciohoz, a g elotagu parancsokat a nezetekhez es a / jelet a listakereses fokuszalasahoz.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Alkalmazason beluli frissitesi osszegzesek',
        'description' => 'A VolumeVault mostantol meg tudja mutatni a felhasznaloknak, mi valtozott egy alkalmazasfrissites utan.',
    ],
    'available_update_checks' => [
        'title' => 'Elerheto frissitesek ellenorzese',
        'description' => 'A VolumeVault mostantol jelezni tudja, ha ujabb GitHub kiadas erheto el.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Torles a feladat reszleteibol',
        'description' => 'A mentesi feladatok mostantol kozvetlenul a reszletek oldalarol torolhetok.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Ertesitesi csatornak feladatonkent',
        'description' => 'A mentesi feladatok mostantol kivalaszthatjak, mely aktiv ertesitesi csatornak kapjak meg az eredmenyeiket.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Alapertelmezett ertesitesek migracioja',
        'description' => 'Ez a kiadas ertesitesi beallitasokat ad a mentesi feladatokhoz es alapertelmezett csatorna kovetest az ertesitesi csatornakhoz.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Host utvonal mentesi forrasok',
        'description' => 'Az adminok kijelolt konyvtarakat menthetnek a Docker hostrol a Docker kotetek mellett.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Host utvonal biztonsagi kontrollok',
        'description' => 'A host utvonalak csak olvashato modon csatolodnak, es korlatozhatok a VOLUMEVAULT_HOST_PATH_ALLOWLIST beallitassal.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Stack mentesi lefedettseg',
        'description' => 'A Docker kotetek Compose vagy Swarm stack szerint csoportosulnak mentesi lefedettsegi allapotokkal.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Mentesi archivum metaadatok',
        'description' => 'A sikeres futasok mostantol megjelenithetik az archivum kulcsokat es mereteket, ha a cel metaadatai elerhetok.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Megbizhato proxy tamogatas',
        'description' => 'A VolumeVault megbizhat a beallitott reverse proxykban, igy a generalt URL-ek a nyilvanos HTTPS semat hasznaljak.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Tisztabb Docker kotet szinkronizalas',
        'description' => 'A szinkronizalas most eltavolitja az elavult hianyzo kotet rekordokat, amelyekre mar nem hivatkoznak mentesi feladatok.',
    ],
    'list_search_and_filters' => [
        'title' => 'Lista kereses es szurok',
        'description' => 'A kotetek es mentesi feladatok keresest, szuroket es keresheto kotetvalasztot kaptak.',
    ],
    'php_85_container_runtime' => [
        'title' => 'PHP 8.5 kontener runtime',
        'description' => 'A kontener atkerult a ServerSideUp PHP 8.5 runtime-ra felugyelt sor es utemezo szolgaltatasokkal.',
    ],
    'first_stable_release' => [
        'title' => 'Elso stabil kiadas',
        'description' => 'A VolumeVault utemezett mentesekkel, biztonsagos visszaallitasokkal, titkositott celokkal, ertesitesekkel, felhasznalokkal, API tokenekkel es telepitesi mentesekkel indult.',
    ],
    'pagination_with_user_preference' => [
        'title' => 'Lapozott listak oldalankenti beallitassal',
        'description' => 'Az osszes listanezet mostmar tamogatja a lapozast konfiguralhato oldalankenti elemszammal (10, 20, 50, 100, vagy Osszes). Alapertelmezett beallitasat a profil beallitasokban adhatja meg.',
    ],
    'dark_pagination_menu' => [
        'title' => 'Sotet lapozasi menu',
        'description' => 'Az oldalankenti elemszam-valaszto megnyitva is megorzi a sotet megjelenest, jobb kontraszttal a lapozott listakban.',
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Megujult elsoleges gombok',
        'description' => 'Az elsoleges muveletgombok most az alkalmazas egeszeben ugyanazt a keretes kek stilust kapjak vilagos es sotet temaban is.',
    ],
    'shareable_filter_urls' => [
        'title' => 'Megoszthato szuro URL-ek',
        'description' => 'A Kotetek, Stackek, Mentesi feladatok es Riasztasok listaszuroi mostmar megjelennek az URL-ben, igy a szurt nezeteket kozvetlenul masolhatja es megoszthatja.',
    ],
];
