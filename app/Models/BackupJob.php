<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupJob extends Model
{
    use HasFactory;

    public const SOURCE_TYPE_DOCKER_VOLUME = 'docker_volume';

    public const SOURCE_TYPE_HOST_PATH = 'host_path';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ERROR = 'error';

    public const STATUS_RUNNING = 'running';

    public const SCHEDULE_HOURLY = 'hourly';

    public const SCHEDULE_DAILY = 'daily';

    public const SCHEDULE_WEEKLY = 'weekly';

    public const SCHEDULE_CRON = 'cron';

    protected $fillable = [
        'name',
        'source_type',
        'volume_name',
        'host_path',
        'backup_destination_id',
        'schedule_type',
        'schedule_config',
        'cron_expression',
        'status',
        'notifications_enabled',
        'pause_reason',
        'last_run_at',
        'next_run_at',
        'last_success_at',
        'last_error',
        'retention_days',
        'retention_count',
        'backup_exclude_regexp',
        'stop_containers_before_backup',
    ];

    protected $appends = [
        'source_label',
    ];

    protected function casts(): array
    {
        return [
            'schedule_config' => 'array',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'last_success_at' => 'datetime',
            'retention_days' => 'integer',
            'retention_count' => 'integer',
            'notifications_enabled' => 'boolean',
            'stop_containers_before_backup' => 'boolean',
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class, 'backup_destination_id');
    }

    public function sourceType(): string
    {
        return $this->source_type ?: self::SOURCE_TYPE_DOCKER_VOLUME;
    }

    public function isDockerVolumeSource(): bool
    {
        return $this->sourceType() === self::SOURCE_TYPE_DOCKER_VOLUME;
    }

    public function isHostPathSource(): bool
    {
        return $this->sourceType() === self::SOURCE_TYPE_HOST_PATH;
    }

    public function sourceName(): string
    {
        return $this->isHostPathSource()
            ? (string) $this->host_path
            : (string) $this->volume_name;
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->sourceName();
    }

    public function runs(): HasMany
    {
        return $this->hasMany(BackupRun::class)->latest();
    }

    public function restoreRuns(): HasMany
    {
        return $this->hasMany(RestoreRun::class)->latest();
    }

    public function notificationChannels(): BelongsToMany
    {
        return $this->belongsToMany(NotificationChannel::class)->withTimestamps();
    }
}
