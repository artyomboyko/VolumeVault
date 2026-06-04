<?php

namespace App\Services\Notifications;

use App\Models\AlertRule;
use App\Models\BackupJob;
use App\Models\NotificationChannel;
use Illuminate\Database\Eloquent\Collection;

class ResolveNotificationChannels
{
    /** @return Collection<int, NotificationChannel> */
    public function forJob(BackupJob $job): Collection
    {
        if (! $job->notifications_enabled) {
            return new Collection;
        }

        return $job->notificationChannels()
            ->where('is_active', true)
            ->get();
    }

    /** @return Collection<int, NotificationChannel> */
    public function forJobAlerts(BackupJob $job): Collection
    {
        if (! $job->alert_notifications_enabled) {
            return new Collection;
        }

        return $job->notificationChannels()
            ->where('is_active', true)
            ->get();
    }

    /** @return Collection<int, NotificationChannel> */
    public function forAlertRule(AlertRule $rule): Collection
    {
        return $rule->notificationChannels()
            ->where('is_active', true)
            ->get();
    }
}
