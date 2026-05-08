<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id', 'equipment_id', 'maintenance_round',
        'maintained_at', 'performed_by', 'result', 'remark', 'created_by',
    ];

    protected $casts = [
        'maintained_at' => 'date',
        'maintenance_round' => 'integer',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
