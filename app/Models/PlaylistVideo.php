<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistVideo extends Model
{
    use HasFactory;

    protected $table = 'playlist_videos';

    protected $fillable = [
        'playlist_state_id',
        'video_id',
        'title',
        'published_at',
        'thumbnail_url',
    ];

    public function playlistState()
    {
        return $this->belongsTo(PlaylistState::class, 'playlist_state_id');
    }

    public function videoDatas()
    {
        return $this->hasMany(VideoData::class, 'playlist_video_id');
    }
}
