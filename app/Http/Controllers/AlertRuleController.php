<?php

namespace App\Http\Controllers;

use App\Actions\Alerts\EnsureAlertRules;
use App\Http\Requests\UpdateAlertRulesRequest;
use App\Models\AlertRule;
use App\Models\NotificationChannel;
use Inertia\Inertia;
use Inertia\Response;

class AlertRuleController extends Controller
{
    public function edit(EnsureAlertRules $ensureAlertRules): Response
    {
        $ensureAlertRules->handle();

        return Inertia::render('Alerts/Settings', [
            'rules' => AlertRule::with('notificationChannels')->orderBy('id')->get()->map(fn (AlertRule $rule): array => $this->serializeRule($rule)),
            'notificationChannels' => NotificationChannel::with('backupJobs')->orderBy('name')->get()->map->safeForFrontend(),
        ]);
    }

    public function update(UpdateAlertRulesRequest $request, EnsureAlertRules $ensureAlertRules)
    {
        $ensureAlertRules->handle();

        foreach ($request->alertRules() as $rule) {
            $alertRule = AlertRule::findOrFail($rule['id']);
            $alertRule->update([
                'enabled' => $rule['enabled'],
                'config' => $rule['config'],
            ]);
            $alertRule->notificationChannels()->sync($rule['notification_channel_ids']);
        }

        return redirect()->route('alerts.settings.edit')->with('success', 'Alert settings updated.');
    }

    private function serializeRule(AlertRule $rule): array
    {
        return [
            'id' => $rule->id,
            'type' => $rule->type->value,
            'enabled' => $rule->enabled,
            'config' => $rule->config ?? [],
            'notification_channel_ids' => $rule->notificationChannels->pluck('id')->values()->all(),
        ];
    }
}
