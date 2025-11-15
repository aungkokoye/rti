<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static create(array $array)
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'class_name',
        'class_id',
        'change_data',
        'operation_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
