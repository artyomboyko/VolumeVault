<?php

namespace App\Http\Controllers;

use App\Concerns\PaginateWithPreference;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\AlertEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    use PaginateWithPreference;

    public function index(Request $request): Response
    {
        $perPage = $this->perPageForRequest($request);

        $query = Alert::with(['rule', 'subject']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhereHas('rule', fn ($q) => $q->where('type', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($severity = $request->input('severity')) {
            $query->where('severity', $severity);
        }

        if ($type = $request->input('type')) {
            $types = array_column(AlertType::cases(), 'value');

            if (in_array($type, $types, true)) {
                $query->whereHas('rule', fn ($q) => $q->where('type', $type));
            }
        }

        $query->orderByRaw('COALESCE(last_triggered_at, created_at) DESC');

        return Inertia::render('Alerts/Index', [
            'alerts' => $this->paginateForInertia($query, $perPage, fn (Alert $alert): array => $this->serializeAlert($alert)),
            'defaultPerPage' => $request->user()->default_per_page ?? 10,
        ]);
    }

    public function show(Alert $alert): Response
    {
        $alert->load(['rule', 'subject', 'events']);

        return Inertia::render('Alerts/Show', [
            'alert' => [
                ...$this->serializeAlert($alert),
                'events' => $alert->events->map(fn (AlertEvent $event): array => [
                    'id' => $event->id,
                    'event_type' => $event->event_type->value,
                    'context' => $event->context ?? [],
                    'created_at' => $event->created_at,
                ])->values()->all(),
            ],
        ]);
    }

    private function serializeAlert(Alert $alert): array
    {
        return [
            'id' => $alert->id,
            'type' => $alert->rule->type->value,
            'status' => $alert->status->value,
            'severity' => $alert->severity->value,
            'message' => $alert->message,
            'context' => $alert->context ?? [],
            'trigger_count' => $alert->trigger_count,
            'first_triggered_at' => $alert->first_triggered_at,
            'last_triggered_at' => $alert->last_triggered_at,
            'resolved_at' => $alert->resolved_at,
            'last_notified_at' => $alert->last_notified_at,
            'subject' => $this->serializeSubject($alert->subject),
            'created_at' => $alert->created_at,
            'updated_at' => $alert->updated_at,
        ];
    }

    private function serializeSubject(?Model $subject): ?array
    {
        if (! $subject) {
            return null;
        }

        return [
            'id' => $subject->getKey(),
            'type' => class_basename($subject),
            'name' => method_exists($subject, 'sourceName') ? $subject->name : ($subject->name ?? '#'.$subject->getKey()),
            'source' => method_exists($subject, 'sourceName') ? $subject->sourceName() : (method_exists($subject, 'targetLabel') ? $subject->targetLabel() : null),
        ];
    }
}
