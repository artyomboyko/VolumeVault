<?php

return [
    'restore_volume_cleanup' => [
        'title' => 'Reprises plus propres apres une restauration echouee',
        'description' => 'Lorsqu\'une restauration echoue apres avoir cree son volume cible, VolumeVault supprime desormais le volume partiellement cree afin que la nouvelle tentative reparte propre, au lieu d\'etre bloquee par une erreur "existe deja".',
    ],
    'stale_run_reconciliation' => [
        'title' => 'Recuperation automatique des runs interrompus',
        'description' => 'Les sauvegardes et restaurations interrompues par un crash, un timeout ou un redemarrage du worker sont maintenant marquees en echec automatiquement au lieu de rester bloquees, pour que les sauvegardes planifiees continuent de tourner. Les conteneurs applicatifs arretes pour une sauvegarde sont aussi redemarres automatiquement si un crash les avait laisses eteints.',
    ],
    'advanced_alerting' => [
        'title' => 'Alerting avance',
        'description' => 'VolumeVault peut surveiller les jobs de backup pour detecter les sauvegardes trop anciennes, les echecs repetes, les erreurs prolongees et les tailles d archives inhabituelles.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Alertes de limite de stockage',
        'description' => 'Les destinations peuvent maintenant definir des seuils absolus warning et critiques avec des canaux de notification dedies.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Navigation mobile amelioree',
        'description' => "L'en-tete mobile utilise maintenant un bouton de menu compact et un panneau de navigation structure au lieu d'empiler tous les liens dans l'en-tete.",
    ],
    'keyboard_shortcuts' => [
        'title' => 'Raccourcis clavier',
        'description' => 'Sur desktop, utilisez Ctrl+K pour la navigation rapide, les raccourcis commencant par g pour les vues et / pour cibler la recherche des listes.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Resumes de mise a jour integres',
        'description' => "VolumeVault peut maintenant montrer aux utilisateurs ce qui a change apres une mise a jour de l'application.",
    ],
    'available_update_checks' => [
        'title' => 'Detection des mises a jour disponibles',
        'description' => 'VolumeVault peut maintenant indiquer quand une nouvelle version GitHub est disponible.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Suppression depuis le detail de tache',
        'description' => 'Les taches de sauvegarde peuvent maintenant etre supprimees directement depuis leur page detail.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Canaux de notification par tache',
        'description' => 'Les taches de sauvegarde peuvent maintenant choisir quels canaux actifs recoivent leurs resultats.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Migration des notifications par defaut',
        'description' => 'Cette version ajoute des parametres de notification aux taches et le suivi du canal par defaut aux canaux de notification.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Sources chemin hote',
        'description' => "Les admins peuvent sauvegarder des dossiers choisis de l'hote Docker en plus des volumes Docker.",
    ],
    'host_path_safety_controls' => [
        'title' => 'Controles de securite des chemins hote',
        'description' => 'Les chemins hote sont montes en lecture seule et peuvent etre limites avec VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Couverture de sauvegarde par stack',
        'description' => 'Les volumes Docker sont regroupes par stack Compose ou Swarm avec leur etat de couverture de sauvegarde.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Metadonnees des archives',
        'description' => "Les executions reussies peuvent maintenant afficher les cles et tailles d'archive quand la destination fournit ces metadonnees.",
    ],
    'trusted_proxy_support' => [
        'title' => 'Support des proxys de confiance',
        'description' => 'VolumeVault peut faire confiance aux proxys inverses configures pour generer des URL avec le schema HTTPS public.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Synchronisation des volumes plus propre',
        'description' => 'La synchronisation supprime maintenant les anciens volumes manquants qui ne sont plus references par des taches.',
    ],
    'list_search_and_filters' => [
        'title' => 'Recherche et filtres dans les listes',
        'description' => 'Les volumes et taches de sauvegarde ont maintenant une recherche, des filtres et un selecteur de volume recherchable.',
    ],
    'php_85_container_runtime' => [
        'title' => 'Runtime conteneur PHP 8.5',
        'description' => 'Le conteneur utilise maintenant le runtime ServerSideUp PHP 8.5 avec file et planificateur supervises.',
    ],
    'first_stable_release' => [
        'title' => 'Premiere version stable',
        'description' => "VolumeVault a ete lance avec sauvegardes planifiees, restaurations sures, destinations chiffrees, notifications, utilisateurs, jetons API et sauvegardes d'installation.",
    ],
    'pagination_with_user_preference' => [
        'title' => 'Listes paginees avec preference par page',
        'description' => "Toutes les vues listees supportent maintenant la pagination avec un nombre d'elements par page configurable (10, 20, 50, 100, ou Tous). Vous pouvez definir votre valeur par defaut dans les parametres du profil.",
    ],
    'dark_pagination_menu' => [
        'title' => 'Menu de pagination en theme sombre',
        'description' => "Le menu du nombre d'elements par page conserve maintenant une palette adaptee au theme sombre lorsqu'il est ouvert, avec un meilleur contraste dans les vues paginees.",
    ],
    'filter_toolbar_action_buttons' => [
        'title' => 'Boutons primaires harmonises',
        'description' => 'Les boutons d action principaux partagent maintenant le meme style souligne bleu dans toute l application, en theme clair comme en theme sombre.',
    ],
    'shareable_filter_urls' => [
        'title' => 'URLs de filtres partageables',
        'description' => 'Les filtres des listes Volumes, Stacks, Taches de sauvegarde et Alertes sont maintenant refletes dans l URL, permettant de copier et partager des vues filtrees directement.',
    ],
];
