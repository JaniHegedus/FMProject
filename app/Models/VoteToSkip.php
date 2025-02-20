<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteToSkip extends Model
{
    use HasFactory;

    protected $fillable = [
        'listener_id',
    ];

    public function listener(): BelongsTo
    {
        return $this->belongsTo(Listener::class);
    }
}
