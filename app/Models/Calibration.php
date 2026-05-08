<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calibration extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id', 'equipment_id',
        'calibrated_at', 'next_due_at',
        'organization', 'calibrator_name', 'calibrator_phone', 'controller_name',
        'result', 'certificate_no', 'cost', 'attachment_path', 'remark',
        'created_by',
    ];

    protected $casts = [
        'calibrated_at' => 'date',
        'next_due_at' => 'date',
        'cost' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
