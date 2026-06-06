<?php

namespace App\Models;

use App\Enums\AlertEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'alert_id',
        'event_type',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => AlertEventType::class,
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public static function record(Alert $alert, AlertEventType $eventType, array $context = []): self
    {
        return self::create([
            'alert_id' => $alert->id,
            'event_type' => $eventType,
            'context' => $context ?: null,
            'created_at' => now(),
        ]);
    }
}
