<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'from_user_id',
        'type',
        'message',
        'school_id',
        'all_parents',
        'sender_role'
    ];

    protected $casts = [
        'all_parents' => 'boolean'
    ];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}