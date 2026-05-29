<?php

return [
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
];
