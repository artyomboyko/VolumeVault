<?php

return [
    'ssrf_destination_guard' => [
        'title' => 'Les destinations de sauvegarde en IP privee sont desormais protegees (SSRF)',
        'description' => 'VolumeVault refuse desormais par defaut de se connecter a une destination de sauvegarde dont l\'hote se resout en une adresse privee, de bouclage (loopback) ou lien-local (y compris le point de terminaison de metadonnees cloud 169.254.169.254). Cela ne concerne que les destinations sur IP privee, comme un NAS local ou un S3/MinIO auto-heberge - les destinations cloud accessibles par une URL publique ne sont pas affectees. Les sauvegardes planifiees continuent de s\'executer, mais le test de destination, la restauration (listing et telechargement) et l\'alerte de quota de stockage sont bloques tant que vous n\'avez pas liste la plage de la destination dans VOLUMEVAULT_SSRF_ALLOWED_IPS (CIDR separes par des virgules, par ex. 192.168.1.0/24). Les canaux de notification ne sont pas concernes.',
    ],
    'host_path_allowlist_fail_closed' => [
        'title' => 'La liste d\'autorisation des chemins hote est desormais fail-closed',
        'description' => 'VOLUMEVAULT_HOST_PATH_ALLOWLIST refuse desormais par defaut : lorsqu\'elle est vide, les sources de sauvegarde par chemin hote et les destinations locales sont refusees au lieu d\'autoriser n\'importe quel chemin. La meme liste protege maintenant aussi les destinations locales, et les chemins sont reverifies a l\'execution pour bloquer les substitutions de liens symboliques. Les installations existantes qui s\'appuyaient sur l\'ancien comportement ouvert doivent lister leurs chemins - executez "php artisan volumevault:host-path-allowlist:audit" pour obtenir la valeur exacte a definir.',
    ],
    'auth_rate_limiting' => [
        'title' => 'Connexion et reinitialisation de mot de passe limitees',
        'description' => 'Les requetes de connexion et de reinitialisation de mot de passe sont desormais limitees a 5 tentatives par minute, ce qui ralentit les attaques par force brute sur le mot de passe administrateur. Au-dela de la limite, une reponse temporaire "trop de requetes" est renvoyee et se reinitialise au bout d\'une minute.',
    ],
    'restore_input_hardening' => [
        'title' => 'Validation renforcee des entrees de restauration et de sauvegarde',
        'description' => 'La sauvegarde selectionnee pour une restauration doit desormais correspondre au listing de la destination, ce qui bloque les cles de traversee de chemin comme "../../etc/passwd". Les noms de volumes Docker sont limites a des caracteres surs, et l\'extraction de restauration est confinee afin qu\'une archive falsifiee ne puisse pas ecrire en dehors du volume cible.',
    ],
    'sftp_host_key_pinning' => [
        'title' => 'Epinglage de la cle d\'hote SSH pour les destinations SFTP',
        'description' => 'Les destinations SSH/SFTP peuvent desormais epingler la cle d\'hote du serveur pour bloquer les attaques de l\'homme du milieu. Utilisez le bouton "Recuperer la cle du serveur" - ou le nouvel endpoint POST /api/v1/destinations/host-key - pour approuver la cle presentee, ou collez une cle d\'hote ou une empreinte SHA256. La cle est verifiee avant tout envoi d\'identifiants, pour les operations SFTP propres a VolumeVault (test, listing, restauration). La laisser vide conserve le comportement precedent.',
    ],
    'api_token_expiration' => [
        'title' => 'Les tokens API expirent desormais par defaut',
        'description' => 'Les tokens API expirent desormais 60 jours apres leur creation par defaut, ce qui limite l\'impact d\'un token divulgue. Les tokens existants plus anciens cessent de fonctionner apres la mise a jour et doivent etre recrees. Definissez SANCTUM_TOKEN_EXPIRATION (en minutes) pour modifier la duree, ou null pour conserver des tokens sans expiration. Une expiration definie par token ne peut que raccourcir cette duree, jamais l\'allonger.',
    ],
    'alert_check_isolation' => [
        'title' => 'Verifications d\'alerte plus robustes',
        'description' => 'Une regle d\'alerte qui echoue n\'empeche plus la verification des autres regles. Chaque regle est desormais evaluee independamment et les echecs sont journalises, de sorte qu\'une seule verification defaillante ne peut plus desactiver silencieusement vos autres alertes.',
    ],
    'restore_volume_cleanup' => [
        'title' => 'Reprises plus propres apres une restauration echouee',
        'description' => 'Lorsqu\'une restauration echoue apres avoir cree son volume cible, VolumeVault supprime desormais le volume partiellement cree afin que la nouvelle tentative reparte propre, au lieu d\'etre bloquee par une erreur "existe deja".',
    ],
    'schedule_drift_prevention' => [
        'title' => 'Planification des sauvegardes plus fiable',
        'description' => 'Les sauvegardes planifiees ne sautent plus d\'execution lorsqu\'un worker prend du retard. La prochaine execution est desormais ancree sur le creneau prevu plutot que sur l\'heure de fin du run precedent, ce qui evite toute derive du planning.',
    ],
    'destination_usage_efficiency' => [
        'title' => 'Calcul de l\'utilisation du stockage plus efficace',
        'description' => 'L\'utilisation du stockage des destinations de sauvegarde est desormais calculee en parcourant les objets en flux plutot qu\'en chargeant toute la liste en memoire, et les connexions SFTP sont toujours fermees ensuite. Les destinations contenant de nombreuses sauvegardes sont mesurees de maniere plus fiable, sans saturer la memoire ni laisser de connexions ouvertes.',
    ],
    'run_log_integrity' => [
        'title' => 'Journaux d\'execution plus fiables',
        'description' => 'Les journaux des executions de sauvegarde et de restauration sont desormais ajoutes de maniere atomique : les mises a jour concurrentes - par exemple un message d\'erreur et une notification de redemarrage de conteneur - ne s\'ecrasent plus mutuellement. Leur taille est aussi plafonnee, en conservant la sortie la plus recente plutot que de grossir sans limite.',
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
    'safer_default_environment_settings' => [
        'title' => 'Parametres d environnement par defaut plus surs',
        'description' => '.env.example utilise maintenant APP_ENV=production et APP_DEBUG=false pour les nouveaux deploiements. Une indication pour SESSION_SECURE_COOKIE est egalement ajoutee afin que les deploiements HTTPS puissent activer des cookies securises sans casser par inadvertance les installations en HTTP seul.',
    ],
];
