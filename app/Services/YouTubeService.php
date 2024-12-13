<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Dotenv\Dotenv;

class YouTubeService
{
    protected $apiKey;
    protected $playlist;

    public function __construct()
    {
        $this->apiKey = config('services.youtube.api_key');
        $this->playlist = config('services.youtube.playlist_id');

        if (!$this->apiKey || !$this->playlist) {
            throw new \Exception("YouTube API key or playlist ID is not configured.");
        }
    }


    public function getPlaylistVideos($key = null)
    {
        $url = "https://www.googleapis.com/youtube/v3/playlistItems";
        $videos = [];
        $pageToken = null;

        do {
            $params = [
                'part' => 'snippet',
                'playlistId' => $this->playlist,
                'maxResults' => 50,
                'pageToken' => $pageToken,
                'key' => $this->apiKey,
            ];

            $response = Http::get($url, $params);

            if ($response->failed()) {
                // Throw an exception with request details
                $message = "YouTube API request failed.\n";
                $message .= "Request URL: $url\n";
                $message .= "Request Parameters: " . json_encode($params, JSON_PRETTY_PRINT) . "\n";
                $message .= "Response: " . $response->body() . "\n";

                throw new \Exception($message);
            }

            $data = $response->json();
            $videos = array_merge($videos, $data['items']);
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        return ['items' => $videos];
    }


    public function getVideoDetails(array $videoIds)
    {
        $url = "https://www.googleapis.com/youtube/v3/videos";
        $videos = [];

        // Chunk video IDs into batches of 50
        $batches = array_chunk($videoIds, 50);

        foreach ($batches as $batch) {
            $params = [
                'id' => implode(',', $batch),
                'part' => 'snippet,contentDetails,status',
                'key' => $this->apiKey,
            ];

            $response = Http::get($url, $params);

            if ($response->failed()) {
                $message = "YouTube API request failed.\n";
                $message .= "Request URL: $url\n";
                $message .= "Request Parameters: " . json_encode($params, JSON_PRETTY_PRINT) . "\n";
                #$message .= "Response: " . $response->body() . "\n";
                throw new \Exception($message);
            }

            $data = $response->json();

            // Validate response structure
            if (isset($data['items'])) {
                $videos = array_merge($videos, $data['items']);
            } else {
                \Log::warning("No video details found for batch: " . json_encode($batch));
            }
        }

        return ['items' => $videos];
    }



}
