<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoData extends Model
{
    use HasFactory;

    protected $table = 'video_datas';

    protected $fillable = [
        'playlist_video_id',
        'description',
        'status',
        'duration',
    ];

    public function playlistVideo()
    {
        return $this->belongsTo(PlaylistVideo::class, 'playlist_video_id');
    }
}