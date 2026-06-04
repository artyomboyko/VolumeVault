<?php

namespace App\Http\Controllers;

use App\Actions\Alerts\EnsureAlertRules;
use App\Http\Requests\UpdateAlertRulesRequest;
use App\Models\AlertRule;
use Inertia\Inertia;
use Inertia\Response;

class AlertRuleController extends Controller
{
    public function edit(EnsureAlertRules $ensureAlertRules): Response
    {
        $ensureAlertRules->handle();

        return Inertia::render('Alerts/Settings', [
            'rules' => AlertRule::orderBy('id')->get()->map(fn (AlertRule $rule): array => $this->serializeRule($rule)),
        ]);
    }

    public function update(UpdateAlertRulesRequest $request, EnsureAlertRules $ensureAlertRules)
    {
        $ensureAlertRules->handle();

        foreach ($request->alertRules() as $rule) {
            AlertRule::whereKey($rule['id'])->update([
                'enabled' => $rule['enabled'],
                'config' => $rule['config'],
            ]);
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
        ];
    }
}
