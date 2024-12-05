<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YouTubeService
{
    protected $apiKey;
    protected $playlist;

    public function __construct()
    {
        $this->apiKey = config('services.youtube.api_key');
        $this->playlist = config('services.youtube.playlist_id');
    }

    public function getPlaylistVideos()
    {
        $url = "https://www.googleapis.com/youtube/v3/playlistItems";

        $response = Http::get($url, [
            'part' => 'snippet',
            'playlistId' => $this->playlist,
            'maxResults' => 50, // Adjust as needed
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new \Exception("YouTube API request failed: " . $response->body());
        }

        return $response->json();
    }

    public function getVideoDetails(array $videoIds)
    {
        $url = "https://www.googleapis.com/youtube/v3/videos";

        $response = Http::get($url, [
            'id' => implode(',', $videoIds),
            'part' => 'snippet,contentDetails,status', // Request additional parts
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new \Exception("YouTube API request failed: " . $response->body());
        }

        return $response->json();
    }

}
