<?php

return [
    'advanced_alerting' => [
        'title' => 'Advanced alerting',
        'description' => 'VolumeVault can monitor backup jobs for stale backups, repeated failures, long-running error states, and unusual archive sizes.',
    ],
    'destination_storage_limit_alerts' => [
        'title' => 'Оповещения о лимите хранилища назначения',
        'description' => 'Назначения резервных копий теперь могут задавать абсолютные пороги предупреждения и критического уровня с отдельными каналами уведомлений.',
    ],
    'mobile_navigation_redesign' => [
        'title' => 'Улучшенная мобильная навигация',
        'description' => 'Мобильная шапка теперь использует компактную кнопку меню и структурированную панель навигации вместо размещения всех ссылок в шапке.',
    ],
    'keyboard_shortcuts' => [
        'title' => 'Горячие клавиши',
        'description' => 'На десктопе используйте Ctrl+K для быстрой навигации, сочетания с префиксом g для разделов и / для фокуса поиска в списках.',
    ],
    'in_app_update_summaries' => [
        'title' => 'Сводки обновлений в приложении',
        'description' => 'VolumeVault теперь может показывать пользователям, что изменилось после обновления приложения.',
    ],
    'available_update_checks' => [
        'title' => 'Проверка доступных обновлений',
        'description' => 'VolumeVault теперь может показывать, когда доступен новый релиз GitHub.',
    ],
    'backup_job_detail_deletion' => [
        'title' => 'Удаление со страницы задания',
        'description' => 'Задания резервного копирования теперь можно удалять прямо со страницы деталей.',
    ],
    'per_job_notification_channels' => [
        'title' => 'Каналы уведомлений для каждого задания',
        'description' => 'Задания резервного копирования теперь могут выбирать, какие активные каналы уведомлений получат их результаты.',
    ],
    'notification_defaults_migration' => [
        'title' => 'Миграция уведомлений по умолчанию',
        'description' => 'Этот релиз добавляет настройки уведомлений к заданиям резервного копирования и отслеживание канала по умолчанию к каналам уведомлений.',
    ],
    'host_path_backup_sources' => [
        'title' => 'Источники резервных копий из пути хоста',
        'description' => 'Администраторы могут создавать копии выбранных каталогов с Docker-хоста вместе с Docker-томами.',
    ],
    'host_path_safety_controls' => [
        'title' => 'Контроли безопасности путей хоста',
        'description' => 'Пути хоста монтируются только для чтения и могут быть ограничены через VOLUMEVAULT_HOST_PATH_ALLOWLIST.',
    ],
    'stack_backup_coverage' => [
        'title' => 'Покрытие резервных копий по стекам',
        'description' => 'Docker-тома группируются по стекам Compose или Swarm со статусами покрытия резервными копиями.',
    ],
    'backup_archive_metadata' => [
        'title' => 'Метаданные архива резервной копии',
        'description' => 'Успешные запуски теперь могут показывать ключи и размеры архивов, когда доступны метаданные назначения.',
    ],
    'trusted_proxy_support' => [
        'title' => 'Поддержка доверенных прокси',
        'description' => 'VolumeVault может доверять настроенным обратным прокси, чтобы создаваемые URL использовали публичную схему HTTPS.',
    ],
    'cleaner_docker_volume_sync' => [
        'title' => 'Более чистая синхронизация Docker-томов',
        'description' => 'Синхронизация теперь удаляет устаревшие записи отсутствующих томов, на которые больше не ссылаются задания резервного копирования.',
    ],
    'list_search_and_filters' => [
        'title' => 'Поиск и фильтры в списках',
        'description' => 'Тома и задания резервного копирования получили поиск, фильтры и селектор томов с поиском.',
    ],
    'php_85_container_runtime' => [
        'title' => 'Среда выполнения контейнера PHP 8.5',
        'description' => 'Контейнер перешел на среду ServerSideUp PHP 8.5 с управляемыми сервисами очереди и планировщика.',
    ],
    'first_stable_release' => [
        'title' => 'Первый стабильный релиз',
        'description' => 'VolumeVault запущен с запланированными копиями, безопасными восстановлениями, зашифрованными назначениями, уведомлениями, пользователями, API-токенами и сохранениями установки.',
    ],
    'pagination_with_user_preference' => [
        'title' => 'Paginated lists with per-page preference',
        'description' => 'All list views now support pagination with configurable items per page (10, 20, 50, 100, or All). You can set your default in Profile settings.',
    ],
];
