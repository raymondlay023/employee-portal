<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_name',
        'trigger_type',
        'triggered_by_user_id',
        'parameters',
        'status',
        'records_fetched',
        'records_processed',
        'records_failed',
        'error_message',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    /**
     * Get the user who triggered the sync manually.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
