<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Equipment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'equipments';

    protected $fillable = [
        'hospital_id', 'department_id', 'equipment_code_id',
        'location_id', 'responsible_user_id',
        'fiscal_year', 'asset_number', 'id_code',
        'name_th', 'name_en', 'manufacturer', 'model', 'serial_number',
        'maintenance_cycles_per_year', 'calibration_by', 'status',
        'purchase_date', 'purchase_price', 'warranty_until',
        'note', 'qr_payload',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'qr_payload' => 'array',
        'purchase_date' => 'date',
        'warranty_until' => 'date',
        'fiscal_year' => 'integer',
        'maintenance_cycles_per_year' => 'integer',
        'purchase_price' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'department_id', 'location_id', 'responsible_user_id',
                'name_th', 'manufacturer', 'model', 'serial_number',
                'status', 'calibration_by', 'note',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function equipmentCode(): BelongsTo
    {
        return $this->belongsTo(EquipmentCode::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function calibrations(): HasMany
    {
        return $this->hasMany(Calibration::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function repairTickets(): HasMany
    {
        return $this->hasMany(RepairTicket::class);
    }

    public function latestCalibration()
    {
        return $this->hasOne(Calibration::class)->latestOfMany('calibrated_at');
    }
}
