<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistState extends Model
{
    use HasFactory;

    protected $table = 'playlist_state';

    protected $fillable = [
        'video_id',
        'start_time',
        'duration',
    ];
}
