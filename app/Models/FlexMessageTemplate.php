<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlexMessageTemplate extends Model
{
    use HasFactory;

    public const KEY_CREATE_EQUIPMENT = 'CREATE_EQUIPMENT';
    public const KEY_REPAIR_REQUEST = 'REPAIR_REQUEST';
    public const KEY_REPAIR_ACKNOWLEDGED = 'REPAIR_ACKNOWLEDGED';
    public const KEY_REPAIR_IN_PROGRESS = 'REPAIR_IN_PROGRESS';
    public const KEY_REPAIR_COMPLETED = 'REPAIR_COMPLETED';
    public const KEY_CALIBRATION_DONE = 'CALIBRATION_DONE';
    public const KEY_CALIBRATION_DUE = 'CALIBRATION_DUE';

    protected $fillable = [
        'hospital_id', 'key', 'name', 'alt_text', 'json_payload',
        'is_active', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
