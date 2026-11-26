<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'event',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFailed(): bool
    {
        return $this->event === self::FAILED;
    }
}
