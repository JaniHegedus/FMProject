<?php
namespace App\Console\Commands;

use App\Services\YouTubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RotatePlaylist extends Command
{
    protected $signature = 'playlist:rotate';
    protected $description = 'Rotate the playlist to the next video';

    public function handle()
    {
        $youtube = new YouTubeService();

        // Step 1: Get the playlist videos
        $playlistResponse = $youtube->getPlaylistVideos();
        $playlistVideos = $playlistResponse['items'] ?? [];

        if (empty($playlistVideos)) {
            $this->error('The playlist is empty.');
            return;
        }

        // Step 2: Extract video IDs
        $videoIds = array_map(function ($video) {
            return $video['snippet']['resourceId']['videoId'] ?? null;
        }, $playlistVideos);

        // Filter out null IDs
        $videoIds = array_filter($videoIds);

        if (empty($videoIds)) {
            $this->error('No valid video IDs found in the playlist.');
            return;
        }

        // Step 3: Get video details (including duration) from the `videos` API
        $videoDetails = $youtube->getVideoDetails($videoIds);
        // Parse durations into seconds
        $videoData = [];
        foreach ($videoDetails['items'] as $video) {
            $videoId = $video['id'];
            $duration = $video['contentDetails']['duration'] ?? null;
            $durationInSeconds = $this->convertDurationToSeconds($duration);

            $videoData[] = [
                'id' => $videoId,
                'duration' => $durationInSeconds,
            ];
        }

        // Step 4: Get the current playlist state
        $currentVideo = DB::table('playlist_state')->first();

        if (!$currentVideo) {
            $this->info('No current video is set. Initializing with the first video.');

            // Set the first video
            DB::table('playlist_state')->insert([
                'video_id' => $videoData[0]['id'],
                'start_time' => now(),
                'duration' => $videoData[0]['duration'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info('Playlist initialized with the first video: ' . $videoData[0]['id']);
            return;
        }

        // Step 5: Find the next video
        $currentIndex = array_search($currentVideo->video_id, array_column($videoData, 'id'));

        if ($currentIndex === false) {
            $this->error('Current video is not in the playlist. Resetting to the first video.');
            $nextIndex = 0;
        } else {
            $nextIndex = ($currentIndex + 1) % count($videoData);
        }

        // Step 6: Update the playlist state
        $nextVideo = $videoData[$nextIndex];
        DB::table('playlist_state')->update([
            'video_id' => $nextVideo['id'],
            'start_time' => now(),
            'duration' => $nextVideo['duration'],
            'updated_at' => now(),
        ]);

        $this->info('Playlist rotated successfully. Next video: ' . $nextVideo['id']);
    }

    private function convertDurationToSeconds($duration)
    {
        if (!$duration) {
            return null;
        }

        preg_match_all('/(\d+)([HMS])/', $duration, $matches, PREG_SET_ORDER);

        $seconds = 0;
        foreach ($matches as $match) {
            $value = (int)$match[1];
            $unit = $match[2];

            if ($unit === 'H') {
                $seconds += $value * 3600;
            } elseif ($unit === 'M') {
                $seconds += $value * 60;
            } elseif ($unit === 'S') {
                $seconds += $value;
            }
        }

        return $seconds;
    }
}
