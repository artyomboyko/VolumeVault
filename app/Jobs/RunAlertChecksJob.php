<?php

namespace App\Jobs;

use App\Actions\Alerts\EnsureAlertRules;
use App\Actions\Alerts\RunAllAlertChecks;
use App\Models\AlertRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RunAlertChecksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function middleware(): array
    {
        return [(new WithoutOverlapping('run-alert-checks'))->expireAfter(600)];
    }

    public function handle(EnsureAlertRules $ensureAlertRules, RunAllAlertChecks $runAllAlertChecks): void
    {
        if (! config('volumevault.alerts.enabled')) {
            return;
        }

        $ensureAlertRules->handle();

        AlertRule::query()
            ->orderBy('id')
            ->get()
            ->each(function (AlertRule $rule) use ($runAllAlertChecks): void {
                $interval = max(1, (int) ($rule->config['check_interval_minutes'] ?? config('volumevault.alerts.defaults.check_interval_minutes', 60)));
                $cacheKey = 'alert_last_check_'.$rule->type->value;
                $lastCheck = Cache::get($cacheKey);

                if ($lastCheck && Carbon::parse($lastCheck)->addMinutes($interval)->isFuture()) {
                    return;
                }

                $runAllAlertChecks->handle($rule);
                Cache::put($cacheKey, now(), now()->addMinutes($interval));
            });
    }
}
