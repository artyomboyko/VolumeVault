<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_rule_id',
        'subject_type',
        'subject_id',
        'status',
        'severity',
        'message',
        'context',
        'trigger_count',
        'first_triggered_at',
        'last_triggered_at',
        'resolved_at',
        'last_notified_at',
    ];

    protected $attributes = [
        'trigger_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => AlertStatus::class,
            'severity' => AlertSeverity::class,
            'context' => 'array',
            'trigger_count' => 'integer',
            'first_triggered_at' => 'datetime',
            'last_triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function events(): HasMany
    {
        return $this->hasMany(AlertEvent::class)->latest('created_at');
    }
}
