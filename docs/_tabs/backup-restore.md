---
title: Backup & Restore
icon: fas fa-rotate
order: 3
---

## How It Works

VolumeVault does not reimplement backup archive creation. Backup runs launch a temporary `offen/docker-volume-backup:latest` container with the selected Docker volume or host path mounted read-only under `/backup`. VolumeVault maps each configured destination to the environment variables expected by `offen/docker-volume-backup`.

Restore runs download the selected archive through VolumeVault's destination layer, create a new Docker volume, then extract the archive using the `offen/docker-volume-backup` image with `tar` as entrypoint.

Docker commands are built with array arguments through Symfony Process. Secrets are passed as process environment variables or temporary mounted secret files and are not logged by VolumeVault.

## Backup Jobs

To create a backup job:

1. Make sure Docker volumes have been synced from the Volumes screen.
2. Create and test at least one active destination.
3. Open `Backup jobs` and create a job for a Docker volume or an absolute host path.
4. Choose a schedule: hourly, daily, weekly, or cron.
5. Optionally set retention days, retention count, file exclusion regexp, and container stop behavior.
6. Save the job and run it manually once to validate the destination and logs.

Backup times are interpreted in `APP_TIMEZONE`. For example, set `APP_TIMEZONE=Europe/Paris` if a daily schedule at `02:00` should run at 02:00 Paris time instead of 02:00 UTC.

Backup jobs can optionally exclude files from the archive with `BACKUP_EXCLUDE_REGEXP`. The value is a Go regular expression matched against each file's full path inside `BACKUP_SOURCES`. For example, `\.log$` excludes log files, `(^|/)cache(/|$)` excludes folders named `cache`, and `(^|/)node_modules(/|$)` excludes `node_modules` folders. Leave the field empty to include everything.

## Host Path Sources

Host path backup jobs mount an existing directory from the Docker host into the temporary Offen container with a read-only Docker bind mount. The path is passed to Docker, while Offen only sees the mounted directory under `/backup`.

Host path rules:

- The path must be absolute, must already exist on the Docker host, and must be a directory.
- The filesystem root `/` is rejected.
- Paths containing `.` or `..` segments are rejected.
- `Stop containers before backup` is only available for Docker volume sources.
- If `VOLUMEVAULT_HOST_PATH_ALLOWLIST` is set, the host path must match one of its comma-separated prefixes.

Example allowlist:

```env
VOLUMEVAULT_HOST_PATH_ALLOWLIST=/srv,/mnt/data,/opt/stacks
```

When the allowlist is configured, jobs outside those prefixes fail validation when saved and the error is shown on the host path field.

## Scheduling

VolumeVault uses Laravel Scheduler and the database queue:

- The scheduler runs `DispatchDueBackupJobsJob` every minute.
- It finds active jobs whose `next_run_at` is due.
- It creates a queued backup run and dispatches `RunBackupJob`.
- A separate scheduled job syncs Docker volumes every five minutes.

For non-Docker local development, run:

```bash
php artisan queue:work --tries=1 --timeout=0
php artisan schedule:work
```

## Backup Engine Details

Backups are run by launching a temporary `offen/docker-volume-backup:latest` container.

The environment variable mapping for `offen/docker-volume-backup` is centralized in `app/Actions/Docker/RunBackupContainer.php`. Check the upstream `offen/docker-volume-backup` documentation if an environment variable changes.

Generated archive names follow this pattern:

```text
volumevault-<safe-source-name>-run-<backup-run-id>.tar.gz
```

## Restore Behavior

Restore-to-new-volume is the implemented restore mode and the default because it does not overwrite the source. Host path backups are also restored into a new Docker volume.

The flow is:

- List backup objects from the destination.
- Select one object.
- Generate or edit a safe target volume name.
- Create a new Docker volume.
- Download the selected archive temporarily.
- Extract the archive into the target volume using the `offen/docker-volume-backup` image with `tar` as entrypoint.

In-place and safe in-place modes are represented in the model and UI but are intentionally disabled until their safety flows are fully implemented.

## Notifications

VolumeVault sends backup notifications through Shoutrrr after a backup run finishes. Channels can apply to all backup jobs or only selected jobs.

Supported guided setup modes:

- Discord webhook.
- Telegram bot.
- Ntfy topic.
- Gotify application token.
- SMTP email.
- Advanced mode with any complete Shoutrrr URL for other supported services.

Notification levels:

- `Errors only`: sends notifications only for failed backup runs.
- `Every backup run`: sends notifications for both successful and failed backup runs.

Notification scopes:

- `All backups`: applies the channel to every backup job.
- `Specific backups`: applies the channel only to selected backup jobs.

Notification URLs are encrypted at rest and never returned to the frontend or API after saving. Use the channel test button after setup to verify the target service.

Channels can optionally override the backup notification title and body with templates. Supported tokens are `{{ job }}`, `{{ volume }}`, `{{ destination }}`, `{{ status }}`, `{{ trigger }}`, `{{ duration }}`, and `{{ error }}`.

Notification tests and delivery run the Shoutrrr CLI image through Docker. Only admins can create, edit, delete, or test notification channels.
