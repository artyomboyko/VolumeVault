# Plan : Systeme d'Alerting Avance

## Resume

Systeme de surveillance proactive des backups et des jobs avec alertes configurables, notifications via les channels existants, auto-resolution et historique complet.

Le MVP se concentre sur les alertes liees aux `BackupJob`. Les destinations, volumes et autres sujets pourront etre ajoutes plus tard avec le meme modele `subject_type` / `subject_id`.

### Decisions finales

| Sujet | Decision |
|---|---|
| Config | Utiliser `config/volumevault.php` |
| Types d'alertes MVP | `backup_too_old`, `job_never_succeeded`, `job_in_error_too_long`, `backup_size_out_of_range` |
| Defaut alertes | Toutes desactivees : `enabled: false` |
| Cron | `RunAlertChecksJob` toutes les 5 minutes |
| Intervalle reel | Respecte `check_interval_minutes` par regle |
| Etat courant | Table `alerts` |
| Historique | Table `alert_events` |
| Unicite alerte | Une ligne par `alert_rule + subject` |
| Resolution auto | Oui, une alerte se resout quand la condition n'est plus remplie |
| Notification resolution | Oui, immediate et sans cooldown |
| Rappels | Optionnels, avec cooldown |
| Channels | Reutiliser les notification channels selectionnes sur le BackupJob |
| Niveau channel | Ignorer `NotificationChannel::notification_level` pour les alertes |
| Toggle notif alertes | Ajouter `backup_jobs.alert_notifications_enabled` |
| Overrides job | Ajouter `backup_jobs.use_custom_alert_settings` + `job_alert_configs` |
| Shortcut | `/alerts` garde `a`, API tokens passe a `t` |

---

## Architecture

```
routes/console.php
Schedule::job(new RunAlertChecksJob)->everyFiveMinutes()->withoutOverlapping()
        │
        ▼
┌────────────────────────┐
│   RunAlertChecksJob    │
│   tourne toutes les    │
│   5 min, mais chaque   │
│   regle respecte son   │
│   check interval       │
└───────┬────────────────┘
        │
        ▼
┌────────────────────┐     ┌──────────────────────────┐
│  AlertRule (4)     │◄────│  JobAlertConfig          │
│  enabled: false    │     │  overrides par job       │
└───────┬────────────┘     └──────────────────────────┘
        │
        ▼
┌────────────────────┐     ┌──────────────────────────┐
│  RunAllAlertChecks │────►│  4 Check Actions          │
│  + auto-resolve    │     │  BackupJob subjects       │
└───────┬────────────┘     └──────────────────────────┘
        │
        ▼
┌────────────────────┐     ┌──────────────────────────┐
│  Alert             │────►│  AlertEvent (history)     │
│  state courant     │     │  triggered/resolved/notif │
└───────┬────────────┘     └──────────────────────────┘
        │
        ▼
┌──────────────────────────────────────────────────┐
│  SendShoutrrrNotification                        │
│  - sendAlert()         → premiere detection      │
│  - sendAlertReminder() → rappels                 │
│  - sendAlertResolved() → notification resolution │
└──────────────────────────────────────────────────┘
```

---

## Tables

### `backup_jobs` additions

| Colonne | Type | Defaut | Description |
|---|---|---|---|
| `use_custom_alert_settings` | boolean | `false` | Si `false`, le job utilise la config globale des alertes |
| `alert_notifications_enabled` | boolean | `true` | Active/desactive seulement les notifications d'alertes pour ce job |
| `last_error_at` | timestamp nullable | `null` | Date a laquelle le job est passe en erreur pour la derniere fois |

`notifications_enabled` continue de concerner uniquement les notifications de resultats de backup. `alert_notifications_enabled` concerne uniquement les notifications du systeme d'alerting. Les checks creent quand meme les alertes visibles dans l'UI si `alert_notifications_enabled` est `false`.

Le channel marque `is_default` est preselectionne a la creation d'un job, mais la source de verite reste la selection de channels du job.

### `alert_rules`

Config globale par type d'alerte.

| Colonne | Type | Defaut | Description |
|---|---|---|---|
| `id` | bigint PK | | |
| `type` | string, unique | | Cle enum `AlertType` |
| `enabled` | boolean | `false` | Toggle global |
| `config` | json | `{ "cooldown_minutes": 1440, "reminder_enabled": false, "check_interval_minutes": 60 }` | Seuils + options |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

Les 4 lignes de `alert_rules` doivent etre initialisees de maniere idempotente en production. Ne pas compter uniquement sur un seeder manuel.

### `job_alert_configs`

Overrides par BackupJob et par type d'alerte.

| Colonne | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `backup_job_id` | FK -> `backup_jobs` (cascadeDelete) | |
| `alert_rule_id` | FK -> `alert_rules` (cascadeDelete) | |
| `enabled` | boolean, nullable | Override du toggle global |
| `config` | json, nullable | Override des seuils |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| | | Unique: `(backup_job_id, alert_rule_id)` |

Important : pas de `use_custom_settings` dans cette table. Le switch global est `backup_jobs.use_custom_alert_settings`.

Si `use_custom_alert_settings = false`, les checks ignorent `job_alert_configs` et utilisent `alert_rules`.

Si `use_custom_alert_settings = true`, les checks appliquent les overrides du job quand ils existent.

### `alerts`

Etat courant d'une alerte. Une seule ligne existe par `alert_rule + subject`.

| Colonne | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `alert_rule_id` | FK -> `alert_rules` | |
| `subject_type` | string | Morph, MVP : `BackupJob` |
| `subject_id` | bigint | |
| `status` | string | `active` / `resolved` |
| `severity` | string | `warning` / `critical` |
| `message` | text | Message lisible |
| `context` | json, nullable | Donnees detaillees, ex. seuil, valeur, `backup_run_id` |
| `trigger_count` | integer, default `0` | Nombre de detections depuis la creation de la ligne |
| `first_triggered_at` | timestamp nullable | Premiere detection connue |
| `last_triggered_at` | timestamp nullable | Derniere detection connue |
| `resolved_at` | timestamp nullable | Derniere resolution |
| `last_notified_at` | timestamp nullable | Derniere notification initiale ou rappel |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Index unique : `(alert_rule_id, subject_type, subject_id)`.

`alerts` n'est pas l'historique. Cette table represente l'etat courant et bascule entre `active` et `resolved`. L'historique complet est dans `alert_events`.

### `alert_events`

Historique complet de chaque alerte, append-only.

| Colonne | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `alert_id` | FK -> `alerts` (cascadeDelete) | |
| `event_type` | string | `triggered`, `resolved`, `notified`, `reminder_sent` |
| `context` | json, nullable | Details : seuil, valeur, channel, type de notification |
| `created_at` | timestamp | |

Evenements trackes :

| event_type | Quand | Exemple context |
|---|---|---|
| `triggered` | Premiere detection ou detection repetee | `{ "severity": "warning", "message": "...", "trigger_count": 3 }` |
| `resolved` | Condition resolue | `{ "final_trigger_count": 5 }` |
| `notified` | Notification initiale ou resolution envoyee | `{ "channel": "Discord", "type": "initial" }` |
| `reminder_sent` | Rappel envoye | `{ "channel": "Discord", "trigger_count": 5 }` |

### Tables retirees du MVP

Ne pas creer :

- `alert_channel`
- `alert_reminder_channel`

Les alertes liees a un job utilisent les notification channels deja selectionnes sur ce `BackupJob`.

---

## Enums PHP

```php
// app/Enums/AlertType.php
enum AlertType: string
{
    case BackupTooOld = 'backup_too_old';
    case JobNeverSucceeded = 'job_never_succeeded';
    case JobInErrorTooLong = 'job_in_error_too_long';
    case BackupSizeOutOfRange = 'backup_size_out_of_range';
}

// app/Enums/AlertSeverity.php
enum AlertSeverity: string
{
    case Warning = 'warning';
    case Critical = 'critical';
}

// app/Enums/AlertStatus.php
enum AlertStatus: string
{
    case Active = 'active';
    case Resolved = 'resolved';
}

// app/Enums/AlertEventType.php
enum AlertEventType: string
{
    case Triggered = 'triggered';
    case Resolved = 'resolved';
    case Notified = 'notified';
    case ReminderSent = 'reminder_sent';
}
```

---

## Config (`config/volumevault.php`)

```php
'alerts' => [
    'enabled' => env('VOLUMEVAULT_ALERTS_ENABLED', true),
    'defaults' => [
        'check_interval_minutes' => 60,
        'cooldown_minutes' => 1440,
        'reminder_enabled' => false,
        'backup_too_old_days' => 7,
        'job_never_succeeded_min_runs' => 3,
        'job_in_error_days' => 3,
        'backup_size_out_of_range_min_bytes' => 1024,
        'backup_size_out_of_range_max_bytes' => 10737418240,
    ],
],
```

---

## Schedule

```php
// routes/console.php
Schedule::job(new RunAlertChecksJob)->everyFiveMinutes()->withoutOverlapping();
```

Le cron tourne toutes les 5 minutes, mais chaque type d'alerte n'est verifie que toutes les N minutes selon sa config effective.

Le job utilise le cache pour tracker le dernier check par type :

```php
public function handle(RunAllAlertChecks $runAllAlertChecks): void
{
    if (! config('volumevault.alerts.enabled')) {
        return;
    }

    $rules = AlertRule::where('enabled', true)->get();

    foreach ($rules as $rule) {
        $cacheKey = "alert_last_check_{$rule->type->value}";
        $interval = $rule->config['check_interval_minutes']
            ?? config('volumevault.alerts.defaults.check_interval_minutes');

        $lastCheck = Cache::get($cacheKey);
        if ($lastCheck && $lastCheck->copy()->addMinutes($interval)->isFuture()) {
            continue;
        }

        $runAllAlertChecks->handle($rule);
        Cache::put($cacheKey, now(), now()->addMinutes($interval));
    }
}
```

---

## 4 types d'alertes

### `backup_too_old`

- Subject : `BackupJob`
- Seuil par defaut : 7 jours
- Ne concerne que les jobs qui ont deja reussi au moins une fois
- Severity : `warning` si `last_success_at` depasse le seuil
- Severity : `critical` si `last_success_at` depasse `2x threshold`
- Logique : `last_success_at IS NOT NULL` et `last_success_at < now() - threshold`
- Resolution : un nouveau backup reussi met `last_success_at` a jour
- Override job : `backup_too_old_days`

### `job_never_succeeded`

- Subject : `BackupJob`
- Seuil par defaut : minimum 3 runs termines avant d'alerter
- Severity : `critical`
- Logique : `last_success_at IS NULL` et au moins `min_runs` runs avec `status IN ('success', 'failed')`
- Couvre les jobs qui n'ont jamais reussi et evite le doublon avec `backup_too_old`
- Resolution : un premier backup reussi met `last_success_at`
- Override job : `job_never_succeeded_min_runs`

### `job_in_error_too_long`

- Subject : `BackupJob`
- Seuil par defaut : 3 jours
- Severity : `critical`
- Logique : `status = 'error'` et `last_error_at < now() - threshold`
- Necessite de renseigner `last_error_at` quand un job passe en erreur
- Resolution : le job n'est plus en erreur
- Override job : `job_in_error_days`

### `backup_size_out_of_range`

- Subject : `BackupJob`
- Seuils par defaut : min 1 KB, max 10 GB
- Compare le dernier `BackupRun` reussi avec `backup_size_bytes` non null
- Si `backup_size_bytes` est null : skip, pas d'alerte
- `backup_run_id` est stocke dans `alerts.context`
- Severity : `warning` si hors plage min/max
- Severity : `critical` si tres hors plage, par exemple `< min / 2` ou `> max * 2`
- Resolution : le dernier backup reussi connu revient dans la plage
- Override job : `backup_size_out_of_range_min_bytes`, `backup_size_out_of_range_max_bytes`

---

## Logique de notification

### Channels

Pour les alertes liees a un `BackupJob` :

- Utiliser les channels retournes par `ResolveNotificationChannels` pour ce job
- Respecter `backup_jobs.alert_notifications_enabled`
- Ignorer `NotificationChannel::notification_level`
- Envoyer `warning` et `critical` sur les memes channels
- Si aucun channel actif : creer l'alerte et l'historique, mais ne pas notifier

### Premiere detection

```
Alerte active creee ou reactivee → sendAlert() sur les channels actifs du job
```

La premiere detection ne respecte pas le cooldown : notification immediate.

### Rappels

```
Condition toujours active → trigger_count++
  → reminder_enabled ?
    → OUI → cooldown depasse ? → sendAlertReminder()
    → NON → skip
```

Les rappels respectent `cooldown_minutes`.

### Resolution

```
Condition resolue → status = resolved → sendAlertResolved()
```

La resolution bypass le cooldown. L'utilisateur doit recevoir la fermeture de boucle immediatement.

### Cooldown

- Minimum `cooldown_minutes` entre chaque rappel pour la meme alerte
- Defaut : 1440 minutes = 24h
- Premiere detection : pas de cooldown
- Resolution : pas de cooldown

---

## Config par BackupJob

Dans l'UI d'edition d'un BackupJob, section `Alert settings` :

```
┌─────────────────────────────────────────────────────────────┐
│  Alert settings                                             │
│                                                             │
│  Alert notifications                  [toggle ON]           │
│  Use custom alert settings            [toggle OFF]          │
│                                                             │
│  When OFF:                                                  │
│    "This job uses the global alert configuration."          │
│                                                             │
│  When ON:                                                   │
│    ┌─────────────────────────────────────────────────────┐  │
│    │ Backup too old                    [ON/OFF]          │  │
│    │ Days: [7___]                                       │  │
│    │ Cooldown: [1440___] min                             │  │
│    │ Reminder: [ON/OFF]                                  │  │
│    └─────────────────────────────────────────────────────┘  │
│    ... pour chaque type                                    │
└─────────────────────────────────────────────────────────────┘
```

Le switch custom correspond a `backup_jobs.use_custom_alert_settings`.

Les notifications d'alertes correspondent a `backup_jobs.alert_notifications_enabled`.

---

## Frontend

### Navbar - lien Alerts + badge

Dans `primaryNav` (`AppLayout.vue`) :

```js
{ label: t('Alerts'), href: '/alerts', shortcutKey: 'a' },
```

Changer le shortcut API tokens :

```js
{ label: t('API tokens'), href: '/api-tokens', shortcutKey: 't' },
```

Badge rose sur le lien si alertes actives > 0 :

```html
<span class="rounded-full bg-rose-400/20 px-2 py-0.5 text-xs text-rose-100">
  {{ activeAlertCount }}
</span>
```

### Pages

| Route | Composant | Description |
|---|---|---|
| `/alerts` | `Alerts/Index.vue` | Liste alertes, filtres type/severity/status |
| `/alerts/{alert}` | `Alerts/Show.vue` | Detail : trigger_count, contexte, historique `alert_events` |
| `/alerts/settings` | `Alerts/Settings.vue` | Config globale des 4 types : toggle, seuils, interval, reminder |
| `/backup-jobs/{id}/edit` | Section dans `BackupJobs/Form.vue` | Alert notifications + custom overrides |

### Badges severity

Utiliser les patterns existants du codebase :

| Severity | Classes |
|---|---|
| `warning` | `bg-amber-400/10 text-amber-200 ring-amber-400/30` |
| `critical` | `bg-rose-400/10 text-rose-200 ring-rose-400/30` |

---

## Fichiers a creer/modifier

### Migrations

| Fichier | Action |
|---|---|
| `database/migrations/xxxx_add_alert_fields_to_backup_jobs_table.php` | Creer |
| `database/migrations/xxxx_create_alert_rules_table.php` | Creer |
| `database/migrations/xxxx_create_job_alert_configs_table.php` | Creer |
| `database/migrations/xxxx_create_alerts_table.php` | Creer |
| `database/migrations/xxxx_create_alert_events_table.php` | Creer |

### Enums

| Fichier | Action |
|---|---|
| `app/Enums/AlertType.php` | Creer |
| `app/Enums/AlertSeverity.php` | Creer |
| `app/Enums/AlertStatus.php` | Creer |
| `app/Enums/AlertEventType.php` | Creer |

### Modeles

| Fichier | Action |
|---|---|
| `app/Models/AlertRule.php` | Creer |
| `app/Models/Alert.php` | Creer |
| `app/Models/AlertEvent.php` | Creer |
| `app/Models/JobAlertConfig.php` | Creer |

### Actions

| Fichier | Action |
|---|---|
| `app/Actions/Alerts/EnsureAlertRules.php` | Creer |
| `app/Actions/Alerts/ResolveEffectiveAlertConfig.php` | Creer |
| `app/Actions/Alerts/AlertCheckAction.php` | Creer interface |
| `app/Actions/Alerts/BackupTooOldCheck.php` | Creer |
| `app/Actions/Alerts/JobNeverSucceededCheck.php` | Creer |
| `app/Actions/Alerts/JobInErrorTooLongCheck.php` | Creer |
| `app/Actions/Alerts/BackupSizeOutOfRangeCheck.php` | Creer |
| `app/Actions/Alerts/RunAllAlertChecks.php` | Creer |

### Jobs

| Fichier | Action |
|---|---|
| `app/Jobs/RunAlertChecksJob.php` | Creer |

### Services

| Fichier | Action |
|---|---|
| `app/Services/Notifications/SendShoutrrrNotification.php` | Modifier : `sendAlert`, `sendAlertReminder`, `sendAlertResolved` |
| `app/Services/Notifications/ResolveNotificationChannels.php` | Ajouter une resolution pour alertes qui ignore `notification_level` |

### Controllers / Requests

| Fichier | Action |
|---|---|
| `app/Http/Controllers/AlertController.php` | Creer |
| `app/Http/Controllers/AlertRuleController.php` | Creer |
| `app/Http/Requests/UpdateAlertRulesRequest.php` | Creer |
| `app/Http/Requests/BackupJobRequest.php` | Modifier pour champs alerting |

### Frontend

| Fichier | Action |
|---|---|
| `resources/js/Components/StatusBadge.vue` | Modifier : `warning`, `critical`, `resolved` |
| `resources/js/Pages/Alerts/Index.vue` | Creer |
| `resources/js/Pages/Alerts/Show.vue` | Creer |
| `resources/js/Pages/Alerts/Settings.vue` | Creer |
| `resources/js/Pages/BackupJobs/Form.vue` | Modifier : section alerting |
| `resources/js/Layouts/AppLayout.vue` | Modifier : nav + badge + shortcut API tokens |
| `resources/js/i18n/locales/*.json` | Ajouter traductions UI |

### Routes

| Fichier | Action |
|---|---|
| `routes/web.php` | Modifier : routes alertes |
| `routes/console.php` | Modifier : schedule `RunAlertChecksJob` |

### Tests

| Fichier | Action |
|---|---|
| `tests/Feature/AlertSystemTest.php` | Creer |

### Config / Changelog

| Fichier | Action |
|---|---|
| `config/volumevault.php` | Modifier : section alerts |
| `config/changelog.php` | Modifier |
| `resources/changelog/{locale}.php` | Ajouter l'entree user-facing |

---

## Ordre d'implementation

1. Migrations : colonnes `backup_jobs`, `alert_rules`, `job_alert_configs`, `alerts`, `alert_events`.
2. Enums : `AlertType`, `AlertSeverity`, `AlertStatus`, `AlertEventType`.
3. Modeles + relations.
4. Initialisation idempotente des `AlertRule` via `EnsureAlertRules`.
5. Config `config/volumevault.php`.
6. `ResolveEffectiveAlertConfig`.
7. Actions de check des 4 types.
8. `RunAllAlertChecks` : trigger, update, resolve, event logging.
9. `RunAlertChecksJob` + schedule.
10. Renseigner `last_error_at` quand un job passe en erreur.
11. Notifications d'alertes dans `SendShoutrrrNotification`.
12. Controllers/routes pour liste, detail, settings.
13. Frontend pages alertes + nav badge.
14. Section alerting dans `BackupJobs/Form.vue`.
15. Traductions UI dans tous les fichiers `resources/js/i18n/locales`.
16. Changelog dans `config/changelog.php` + `resources/changelog/{locale}.php`.
17. Tests pour chaque check, resolution, rappels, cooldown, notifications, overrides job.
18. Verification : tests cibles, `./vendor/bin/pint --dirty --format agent`, puis `npm run build`.
