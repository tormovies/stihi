<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeepSeekLog extends Model
{
    public $timestamps = false;
    protected $table = 'deepseek_logs';
    protected $fillable = [
        'status',
        'entity_type',
        'request_payload',
        'request_full',
        'response_raw',
        'processed_count',
        'failed_ids',
        'error_message',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'request_payload' => 'array',
        'failed_ids' => 'array',
    ];
}
