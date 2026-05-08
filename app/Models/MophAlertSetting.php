<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MophAlertSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id', 'is_enabled', 'endpoint_url',
        'client_key', 'secret_key',
        'notify_on_create_equipment', 'notify_on_repair_request',
        'notify_on_repair_progress', 'notify_on_calibration',
        'last_test_at', 'last_test_status',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'notify_on_create_equipment' => 'boolean',
        'notify_on_repair_request' => 'boolean',
        'notify_on_repair_progress' => 'boolean',
        'notify_on_calibration' => 'boolean',
        'last_test_at' => 'datetime',
    ];

    protected $hidden = ['secret_key'];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
