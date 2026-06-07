<?php

return [
    'advanced_alerting' => [
        'title' => 'Advanced alerting',
        'description' => 'VolumeVault can monitor backup jobs for stale backups, repeated failures, long-running error states, and unusual archive sizes.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Alertas de limite de almacenamiento',
        'description' => 'Los destinos ahora pueden definir umbrales absolutos de advertencia y criticos con canales de notificacion dedicados.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Navegacion movil mejorada',
        'description' => 'El encabezado movil ahora usa un boton de menu compacto y un panel de navegacion estructurado en lugar de apilar todos los enlaces en el encabezado.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Atajos de teclado',
        'description' => 'En escritorio, use Ctrl+K para navegacion rapida, atajos con prefijo g para vistas y / para enfocar la busqueda de listas.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Resumenes de actualizacion integrados',
        'description' => 'VolumeVault ahora puede mostrar a los usuarios que cambio despues de una actualizacion de la aplicacion.',
    ],
    'available_update_checks' => [
        'title' => 'Comprobaciones de actualizaciones disponibles',
        'description' => 'VolumeVault ahora puede indicar cuando hay una version nueva disponible en GitHub.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Eliminacion desde el detalle de tarea',
        'description' => 'Las tareas de copia ahora pueden eliminarse directamente desde su pagina de detalle.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Canales de notificacion por tarea',
        'description' => 'Las tareas de copia ahora pueden elegir que canales de notificacion activos reciben sus resultados.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Migracion de notificaciones predeterminadas',
        'description' => 'Esta version agrega ajustes de notificacion a las tareas de copia y seguimiento del canal predeterminado a los canales de notificacion.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Fuentes de ruta del host',
        'description' => 'Los administradores pueden respaldar directorios seleccionados del host Docker junto con volumenes Docker.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Controles de seguridad de rutas del host',
        'description' => 'Las rutas del host se montan solo lectura y pueden restringirse con VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Cobertura de copia por stack',
        'description' => 'Los volumenes Docker se agrupan por stack Compose o Swarm con estados de cobertura de copia.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Metadatos de archivo de copia',
        'description' => 'Las ejecuciones exitosas ahora pueden mostrar claves y tamanos de archivo cuando hay metadatos del destino.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Soporte de proxies confiables',
        'description' => 'VolumeVault puede confiar en proxies inversos configurados para que las URL generadas usen el esquema HTTPS publico.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Sincronizacion de volumenes mas limpia',
        'description' => 'La sincronizacion ahora elimina registros de volumenes ausentes que ya no estan referenciados por tareas de copia.',
    ],
    'list_search_and_filters' => [
        'title' => 'Busqueda y filtros en listas',
        'description' => 'Los volumenes y las tareas de copia ahora tienen busqueda, filtros y un selector de volumen buscable.',
    ],
    'php_85_container_runtime' => [
        'title' => 'Runtime de contenedor PHP 8.5',
        'description' => 'El contenedor paso al runtime ServerSideUp PHP 8.5 con servicios supervisados de cola y planificador.',
    ],
    'first_stable_release' => [
        'title' => 'Primera version estable',
        'description' => 'VolumeVault se lanzo con copias programadas, restauraciones seguras, destinos cifrados, notificaciones, usuarios, tokens API y copias de instalacion.',
    ],
    'pagination_with_user_preference' => [
        'title' => 'Listas paginadas con preferencia por pagina',
        'description' => 'Todas las vistas de lista ahora soportan paginacion con configuracion de elementos por pagina (10, 20, 50, 100, o Todos). Puede establecer su predeterminado en la configuracion del perfil.',
    ],
    'dark_pagination_menu' => [
        'title' => 'Menu oscuro de paginacion',
        'description' => 'El selector de elementos por pagina mantiene ahora una apariencia oscura al abrirse, con mejor contraste en las vistas de lista paginadas.',
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Botones primarios renovados',
        'description' => 'Los botones de accion primarios ahora comparten el mismo estilo azul delineado en toda la aplicacion, tanto en modo claro como oscuro.',
    ],
    'shareable_filter_urls' => [
        'title' => 'URLs de filtros compatibles',
        'description' => 'Los filtros de listas de Volumenes, Stacks, Tareas de copia y Alertas ahora se reflejan en la URL, permitiendo copiar y compartir vistas filtradas directamente.',
    ],
];
