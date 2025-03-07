<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayListPool extends Model
{
    use HasFactory;

    protected $table = 'playlist_pool';
    protected $fillable = [
        'video_id',
        'created_by',
        'votes',
        'voted_by'
    ];
}
