<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name_th', 'name_en', 'risk_level_id',
        'default_calibration_cycle_months', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_calibration_cycle_months' => 'integer',
    ];

    public function riskLevel(): BelongsTo
    {
        return $this->belongsTo(RiskLevel::class);
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
