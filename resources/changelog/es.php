<?php

return [
    'alert_check_isolation' => [
        'title' => 'Comprobaciones de alerta mas robustas',
        'description' => 'Una regla de alerta que falla ya no impide que se comprueben las demas reglas. Cada regla se evalua ahora de forma independiente y los fallos se registran, de modo que una sola comprobacion defectuosa ya no puede desactivar silenciosamente tus demas alertas.',
    ],
    'restore_volume_cleanup' => [
        'title' => 'Reintentos mas limpios tras una restauracion fallida',
        'description' => 'Cuando una restauracion falla despues de crear su volumen de destino, VolumeVault ahora elimina el volumen creado parcialmente para que el siguiente reintento empiece limpio en lugar de quedar bloqueado por un error de "ya existe".',
    ],
    'schedule_drift_prevention' => [
        'title' => 'Programacion de copias de seguridad mas fiable',
        'description' => 'Las copias de seguridad programadas ya no se saltan una ejecucion cuando un worker se retrasa. La proxima ejecucion ahora se ancla en la franja prevista en lugar de la hora de finalizacion de la ejecucion anterior, de modo que una ejecucion lenta o retrasada ya no puede desviar la programacion.',
    ],
    'destination_usage_efficiency' => [
        'title' => 'Calculo mas eficiente del uso de almacenamiento del destino',
        'description' => 'El uso de almacenamiento de los destinos de copia de seguridad ahora se calcula recorriendo los objetos en flujo en lugar de cargar toda la lista en memoria, y las conexiones SFTP siempre se cierran despues. Los destinos con muchas copias de seguridad se miden de forma mas fiable, sin agotar la memoria ni dejar conexiones abiertas.',
    ],
    'run_log_integrity' => [
        'title' => 'Registros de ejecucion mas fiables',
        'description' => 'Los registros de las ejecuciones de copia de seguridad y restauracion ahora se anaden de forma atomica, de modo que las actualizaciones simultaneas - como un mensaje de error y un aviso de reinicio de contenedor - ya no se sobrescriben entre si. Ademas su tamano esta limitado, conservando la salida mas reciente en lugar de crecer sin limite.',
    ],
    'stale_run_reconciliation' => [
        'title' => 'Recuperacion automatica de ejecuciones interrumpidas',
        'description' => 'Las ejecuciones de copia y restauracion interrumpidas por un fallo del worker, un timeout o un reinicio ahora se marcan automaticamente como fallidas en lugar de quedarse bloqueadas, para que las copias programadas sigan ejecutandose. Los contenedores de aplicacion detenidos para una copia tambien se reinician automaticamente si un fallo los dejo apagados.',
    ],
    'advanced_alerting' => [
        'title' => 'Alertas avanzadas',
        'description' => 'VolumeVault puede supervisar las tareas de copia para detectar copias obsoletas, fallos repetidos, estados de error prolongados y tamanos de archivo inusuales.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Alertas de limite de almacenamiento',
        'description' => 'Los destinos de copia ahora pueden definir umbrales absolutos de advertencia y criticos con canales de notificacion de alertas dedicados.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Navegacion movil mejorada',
        'description' => 'El encabezado movil ahora usa un boton de menu compacto y un panel de navegacion estructurado en lugar de apilar todos los enlaces en el encabezado.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Atajos de teclado',
        'description' => 'En escritorio, use Ctrl+K para navegacion rapida, atajos con prefijo g para las vistas y / para enfocar la busqueda de listas.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Resumenes de actualizacion integrados',
        'description' => 'VolumeVault ahora puede mostrar a los usuarios lo que cambio despues de una actualizacion de la aplicacion.',
    ],
    'available_update_checks' => [
        'title' => 'Deteccion de actualizaciones disponibles',
        'description' => 'VolumeVault ahora puede indicar cuando hay una nueva version de GitHub disponible.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Eliminacion desde el detalle de la tarea',
        'description' => 'Las tareas de copia ahora pueden eliminarse directamente desde su pagina de detalle.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Canales de notificacion por tarea',
        'description' => 'Las tareas de copia ahora pueden elegir que canales de notificacion activos reciben sus resultados.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Migracion de notificaciones predeterminadas',
        'description' => 'Esta version agrega ajustes de notificacion a las tareas de copia y el seguimiento del canal predeterminado a los canales de notificacion.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Fuentes de ruta del host',
        'description' => 'Los administradores pueden respaldar directorios seleccionados del host Docker junto con los volumenes Docker.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Controles de seguridad de rutas del host',
        'description' => 'Las rutas del host se montan en modo solo lectura y pueden restringirse con VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Cobertura de copia por stack',
        'description' => 'Los volumenes Docker se agrupan por stack Compose o Swarm con estados de cobertura de copia.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Metadatos del archivo de copia',
        'description' => 'Las ejecuciones exitosas ahora pueden mostrar las claves y los tamanos de archivo cuando hay metadatos del destino disponibles.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Soporte de proxies de confianza',
        'description' => 'VolumeVault puede confiar en los proxies inversos configurados para que las URL generadas usen el esquema HTTPS publico.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Sincronizacion de volumenes Docker mas limpia',
        'description' => 'La sincronizacion ahora elimina los registros de volumenes ausentes obsoletos que ya no estan referenciados por tareas de copia.',
    ],
    'list_search_and_filters' => [
        'title' => 'Busqueda y filtros en las listas',
        'description' => 'Los volumenes y las tareas de copia ahora tienen busqueda, filtros y un selector de volumen con busqueda.',
    ],
    'php_85_container_runtime' => [
        'title' => 'Runtime de contenedor PHP 8.5',
        'description' => 'El contenedor paso al runtime ServerSideUp PHP 8.5 con servicios supervisados de cola y planificador.',
    ],
    'first_stable_release' => [
        'title' => 'Primera version estable',
        'description' => 'VolumeVault se lanzo con copias programadas, restauraciones seguras, destinos cifrados, notificaciones, usuarios, tokens API y copias de la instalacion.',
    ],
    'pagination_with_user_preference' => [
        'title' => 'Listas paginadas con preferencia por pagina',
        'description' => 'Todas las vistas de lista ahora admiten paginacion con un numero de elementos por pagina configurable (10, 20, 50, 100 o Todos). Puede establecer su valor predeterminado en los ajustes del perfil.',
    ],
    'dark_pagination_menu' => [
        'title' => 'Menu de paginacion en tema oscuro',
        'description' => 'El menu desplegable de elementos por pagina ahora conserva una paleta de tema oscuro cuando esta abierto, mejorando el contraste en las vistas de lista paginadas.',
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Botones primarios renovados',
        'description' => 'Los botones de accion primarios ahora comparten el mismo estilo azul delineado en toda la aplicacion, tanto en modo claro como en modo oscuro.',
    ],
    'shareable_filter_urls' => [
        'title' => 'URL de filtros compartibles',
        'description' => 'Los filtros de las listas de Volumenes, Stacks, Tareas de copia y Alertas ahora se reflejan en la URL, para que pueda copiar y compartir vistas filtradas directamente.',
    ],
];
