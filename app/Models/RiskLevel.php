<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name_th', 'sort_order', 'color_hex',
        'recommended_calibration_months', 'description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'recommended_calibration_months' => 'integer',
    ];

    public function equipmentCodes(): HasMany
    {
        return $this->hasMany(EquipmentCode::class);
    }
}
