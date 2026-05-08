<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id', 'template_key', 'event_signature',
        'payload_snapshot', 'response_code', 'response_body', 'sent_at',
    ];

    protected $casts = [
        'payload_snapshot' => 'array',
        'sent_at' => 'datetime',
        'response_code' => 'integer',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
