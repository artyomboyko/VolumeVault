---
title: Destinations
icon: fas fa-database
order: 2
---

## Backup Destinations

VolumeVault is not S3-only. It supports the destination families exposed by the current destination layer and mapped to `offen/docker-volume-backup` when running backups:

- S3-compatible storage: AWS S3, Cloudflare R2, and custom S3-compatible endpoints.
- WebDAV: URL, optional path, optional basic auth, and optional insecure TLS mode.
- SSH/SFTP: host, port, remote path, username, password or private key, and an optional pinned host key.
- Azure Blob Storage: container plus account key or connection string.
- Dropbox: remote path, app key, app secret, refresh token, and concurrency level.
- Google Drive: folder ID and service account JSON, with optional domain-wide delegation subject.
- Local filesystem: archive path shared between VolumeVault and the temporary Offen container.

Each destination can be tested from the UI. Destination testing, listing, upload, download, and restore download behavior is centralized in `app/Services/BackupDestinations/DestinationStorage.php`.

Local destinations require special care in Docker deployments. The configured archive path must be readable by VolumeVault for listing/restores and mounted into the temporary Offen backup container for writes.

### Host path allowlist

The **archive path** (and the optional **Docker mount source**) are held to the same fail-closed host path allowlist as host-path backup sources. They must sit under a prefix listed in the `VOLUMEVAULT_HOST_PATH_ALLOWLIST` environment variable (comma-separated), and they cannot contain a colon (`:`), commas, or `.`/`..` segments.

The allowlist is empty by default, so **no local path is accepted out of the box**. If you try to create a local destination without configuring it, the form rejects the path with an error such as *"Host path access is disabled…"* or *"Host path is outside VOLUMEVAULT_HOST_PATH_ALLOWLIST…"* and stays on the create page. Set the variable, then clear the config cache:

```bash
# .env
VOLUMEVAULT_HOST_PATH_ALLOWLIST=/archive,/mnt/backups

php artisan config:clear
```
