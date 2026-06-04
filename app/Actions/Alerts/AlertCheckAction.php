<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Models\BackupJob;

interface AlertCheckAction
{
    /**
     * @return array<int, array{subject: BackupJob, severity: AlertSeverity, message: string, context: array<string, mixed>}>
     */
    public function handle(AlertRule $rule): array;
}
