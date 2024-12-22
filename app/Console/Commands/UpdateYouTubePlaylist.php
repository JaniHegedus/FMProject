<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Models
use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use App\Models\VideoData;

// Your custom service
use App\Services\YouTubeService;

class UpdateYouTubePlaylist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan playlist:update
     */
    protected $signature = 'playlist:update';

    /**
     * The console command description.
     */
    protected $description = 'Check if the playlist was updated today; if not, truncate tables and reload from YouTube.';

    public function handle(YouTubeService $youtubeService)
    {
        // We only do logic if the DB is outdated
        $today = Carbon::today()->toDateString();

        // For instance, check the latest updated_at in playlist_videos
        $latestUpdate = PlaylistVideo::max('updated_at');

        // If no data or older than today => reload
        if (!$latestUpdate || Carbon::parse($latestUpdate)->toDateString() < $today) {
            $this->info("Data is not up to date. Truncating and reloading...");

            // For SQLite, TRUNCATE might not be supported, so let's do a full delete
            DB::statement('DELETE FROM video_datas;');
            DB::statement('DELETE FROM playlist_videos;');

            // Optionally reset the auto-increment (doesn't always work in SQLite)
            // DB::statement('DELETE FROM sqlite_sequence WHERE name="video_datas";');
            // DB::statement('DELETE FROM sqlite_sequence WHERE name="playlist_videos";');

            // (1) Fetch the entire playlist from YouTube
            $playlistData = $youtubeService->getPlaylistVideos();
            $videoItems = $playlistData['items'] ?? [];

            // (2) Make sure we have a row in playlist_state or create one
            // e.g. if you only want exactly one row in playlist_state
            $playlistState = PlaylistState::firstOrCreate([
                'video_id' => 'main-playlist',
            ], [
                'start_time' => now(),
                'duration'   => 0,
            ]);

            // Collect IDs of all videos
            $videoIds = [];
            foreach ($videoItems as $item) {
                $snippet = $item['snippet'] ?? [];
                $vId = $snippet['resourceId']['videoId'] ?? null;
                if ($vId) {
                    $videoIds[] = $vId;
                }
            }

            // (3) Get details for those videos
            $videoDetails = $youtubeService->getVideoDetails($videoIds);
            $detailItems = $videoDetails['items'] ?? [];

            // (4) Insert records into playlist_videos and video_datas
            foreach ($detailItems as $v) {
                $vId = $v['id'];
                $snippet = $v['snippet'] ?? [];
                $contentDetails = $v['contentDetails'] ?? [];
                $status         = $v['status'] ?? [];

                // Insert into playlist_videos
                $pVideo = PlaylistVideo::create([
                    'playlist_state_id' => $playlistState->id,
                    'video_id'          => $vId,
                    'title'             => $snippet['title'] ?? null,
                    'published_at'      => $snippet['publishedAt'] ?? null,
                    'thumbnail_url'     => $snippet['thumbnails']['default']['url'] ?? null,
                ]);

                // Insert into video_datas
                VideoData::create([
                    'playlist_video_id' => $pVideo->id,
                    'description'       => $snippet['description'] ?? null,
                    'status'            => $status['uploadStatus'] ?? null,
                    'duration'          => $contentDetails['duration'] ?? null,
                ]);
            }

            $this->info("Reloaded " . count($detailItems) . " videos from YouTube.");
        } else {
            $this->info("Data is already up-to-date for today. No reload required.");
        }
    }
}
