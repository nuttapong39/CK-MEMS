<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrcodeTemplate extends Model
{
    use HasFactory;

    protected $table = 'qrcode_templates';

    protected $fillable = [
        'hospital_id', 'name', 'paper_size', 'qr_size_mm',
        'fields_to_show', 'layout_html', 'is_default', 'updated_by',
    ];

    protected $casts = [
        'fields_to_show' => 'array',
        'is_default' => 'boolean',
        'qr_size_mm' => 'integer',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
