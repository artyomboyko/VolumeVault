<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Model;

interface AlertCheckAction
{
    /**
     * @return array<int, array{subject: Model, severity: AlertSeverity, message: string, context: array<string, mixed>}>
     */
    public function handle(AlertRule $rule): array;
}
