<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RepairTicket extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACKNOWLEDGED = 'ACKNOWLEDGED';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_WAITING_PARTS = 'WAITING_PARTS';
    public const STATUS_REPAIRED = 'REPAIRED';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'hospital_id', 'ticket_no', 'equipment_id', 'location_id',
        'reported_at', 'reported_by', 'symptom', 'urgency', 'status',
        'assigned_to', 'acknowledged_at', 'started_at', 'completed_at', 'closed_at',
        'root_cause', 'action_taken', 'parts_used', 'repair_cost',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'repair_cost' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'urgency', 'assigned_to', 'symptom', 'action_taken'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(RepairProgressLog::class)->orderBy('changed_at');
    }
}
