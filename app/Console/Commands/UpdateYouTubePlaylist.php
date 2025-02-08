<?php

namespace App\Console\Commands;

use Exception;
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
    /**
     * The region code for filtering.
     * Consider moving this to a configuration file or environment variable for flexibility.
     */
    protected $region = 'HU';

    /**
     * @throws Exception
     */
    public function handle(YouTubeService $youtubeService): void
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
            $i=0;
            foreach ($detailItems as $v) {
                if (!$this->isVideoValid($v)) {
                    // Video does not meet the criteria; skip insertion
                    continue;
                }
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
                $i++;
            }

            $this->info("Reloaded $i videos from YouTube.");
        } else {
            $this->info("Data is already up-to-date for today. No reload required.");
        }
    }
    protected function isVideoValid(array $videoDetail): bool
    {
        // Define the region code
        $region = $this->region;

        // 1. Check if 'status' and 'contentDetails' are present
        if (empty($videoDetail['status']) || empty($videoDetail['contentDetails'])) {
            return false;
        }

        // 2. Skip age-restricted videos
        if (isset($videoDetail['contentDetails']['contentRating']['ytRating']) &&
            $videoDetail['contentDetails']['contentRating']['ytRating'] === 'ytAgeRestricted') {
            return false;
        }

        // 3. Skip live broadcasts or upcoming streams
        if (isset($videoDetail['snippet']['liveBroadcastContent']) &&
            $videoDetail['snippet']['liveBroadcastContent'] !== 'none') {
            return false;
        }

        // 4. Ensure the video is public
        if (!isset($videoDetail['status']['privacyStatus']) ||
            $videoDetail['status']['privacyStatus'] !== 'public') {
            return false;
        }

        // 5. Ensure the video is embeddable
        if (isset($videoDetail['status']['embeddable']) &&
            $videoDetail['status']['embeddable'] === false) {
            return false;
        }

        // 6. Check for region restrictions
        $regionRestriction = $videoDetail['contentDetails']['regionRestriction'] ?? null;
        if ($regionRestriction) {
            // If the region is blocked, skip the video
            if (isset($regionRestriction['blocked']) &&
                in_array($region, $regionRestriction['blocked'])) {
                return false;
            }

            // If there's an allowlist and the region isn't allowed, skip the video
            if (isset($regionRestriction['allowed']) &&
                !in_array($region, $regionRestriction['allowed'])) {
                return false;
            }
        }

        // 7. Ensure the video has a duration
        if (empty($videoDetail['contentDetails']['duration'])) {
            return false;
        }

        // 8. Convert duration from ISO 8601 to seconds and ensure it's positive
        $durationInSeconds = $this->convertDurationToSeconds($videoDetail['contentDetails']['duration']);
        if (!$durationInSeconds) {
            return false;
        }

        // All checks passed; video is valid
        return true;
    }
    protected function convertDurationToSeconds(string $duration): ?int
    {
        if (!$duration) {
            return null;
        }

        try {
            $interval = new \DateInterval($duration);
        } catch (\Exception $e) {
            // Invalid duration format
            Log::error("Invalid ISO 8601 duration format: {$duration}");
            return null;
        }

        $seconds = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;

        // Include days if present
        if ($interval->d) {
            $seconds += $interval->d * 86400;
        }

        // Include months and years if present (approximate)
        if ($interval->m) {
            $seconds += $interval->m * 2592000; // 30 days per month
        }
        if ($interval->y) {
            $seconds += $interval->y * 31536000; // 365 days per year
        }

        return $seconds > 0 ? $seconds : null;
    }
}
