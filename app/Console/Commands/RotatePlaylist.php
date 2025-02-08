<?php

namespace App\Console\Commands;

use App\Models\PlayListPool;
use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use App\Models\VideoData;
use Carbon\Carbon;
use Illuminate\Console\Command;
use React\EventLoop\Factory;

class RotatePlaylist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * You can run: php artisan playlist:rotate
     */
    protected $signature = 'playlist:rotate {--force : Force immediate rotation ignoring remaining time}';

    /**
     * The console command description.
     */
    protected $description = 'Rotate the playlist from our DB using ReactPHP timers (long-running).';

    protected $loop;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // 1) Create the ReactPHP event loop
        $this->loop = Factory::create();
        $force = $this->option('force');

        $this->info("Starting DB-based rotation. Press Ctrl+C to stop.");

        // 2) Schedule the first rotation
        $this->scheduleRotation($force);

        // 3) Run the event loop (never ends until you kill it)
        $this->loop->run();
    }

    /**
     * Schedule a rotation after the "current" track ends.
     */
    protected function scheduleRotation($force = false): void
    {
        $duration = $this->rotatePlaylistFromDb($force);
        #$this->info($duration);
        if ($duration && $duration > 0) {
            // Schedule next rotation exactly after $duration seconds
            $this->info("Next rotation scheduled in {$duration} seconds...");
            $this->loop->addTimer($duration, function () {
                $this->scheduleRotation();
            });
        } else {
            // If we didn’t get a valid duration, wait 10s and retry
            $this->warn("No valid duration returned. Will retry in 10 seconds...");
            $this->loop->addTimer(10, function () {
                $this->scheduleRotation();
            });
        }
    }

    /**
     * Rotate to the next video from the DB.
     * Returns the new video’s duration (in seconds) or null/0 if something fails.
     */
    protected function rotatePlaylistFromDb(bool $force = false)
    {
        // Step 1: Get all playlist videos from DB
        // Make sure these tables are populated by your "youtube:update-playlist" or similar
        $playlistVideos = PlaylistVideo::with('videoDatas')->get();
        $requester = '';
        if($force) {
            $requester .= 'Forced Rotation';
        }
        if ($playlistVideos->isEmpty()) {
            $this->error("No videos in playlist_videos table.");
            return 0;
        }

        // Step 2: Build an array of {id, durationInSeconds} from DB
        $videoData = [];
        foreach ($playlistVideos as $pv) {
            // If multiple VideoData records exist, pick whichever you like
            $vd = $pv->videoDatas->first();
            if (!$vd) {
                // No data? Skip or set 0
                continue;
            }

            // We assume 'duration' is an ISO 8601 string (e.g. "PT4M13S")
            // If you already store raw seconds, skip conversion
            $isoDuration = $vd->duration;
            $seconds     = $this->convertDurationToSeconds($isoDuration);

            if ($seconds > 0) {
                $videoData[] = [
                    'id'       => $pv->video_id,
                    'duration' => $seconds,
                ];
            }
        }

        if (empty($videoData)) {
            $this->error("No valid durations found in the DB.");
            return 0;
        }

        // Step 3: Get the current video from the playlist_state table
        $currentVideo = PlaylistState::first();
        // Convert start_time to Carbon object
        $startTime = Carbon::parse($currentVideo->start_time);

        // Get the current time as a Carbon instance
        $currentTime = Carbon::now();

        // Add duration (in seconds) to start time to get the end time
        $endTime = $startTime->addSeconds($currentVideo->duration);

        // Calculate remaining time by getting the difference in seconds
        $currentRemaining = $currentTime->diffInSeconds($endTime, false); // The 'false' means we get the difference in seconds, and it can be negative if the end time is in the past
        // Step 4: Find the current video index
        $currentIndex = false;
        if ($currentVideo) {
            $currentIndex = array_search($currentVideo->video_id, array_column($videoData, 'id'));
        }

        // Step 5: Determine the next index
        if ($currentIndex === false) {
            $this->line("Current video not found in DB. Resetting to the first.");
            $nextIndex = 0;
        } else {
            $nextIndex = ($currentIndex + 1) % count($videoData);
        }

        // Step 6: Update playlist_state with the next video
        $nextVideo = $videoData[$nextIndex];
        if($currentRemaining > 0 && !$force) {
            $this->info('Waiting for current video to finish. Waiting: '.$currentRemaining.' seconds...');
            sleep($currentRemaining);
            $this->rotatePlaylistFromDb();
            return $currentVideo->duration;
        }else {
            $pollwinner = PlaylistPool::where('created_at', '<=', Carbon::now()->subMinutes(10))
                ->orderBy('votes', 'desc')
                ->first();

            if($pollwinner){
                $video_id = $pollwinner->video_id;
                $playlist_video = PlaylistVideo::where('video_id',$video_id)->first();
                $video_details = VideoData::where('playlist_video_id',$playlist_video->id)->first();
                $this->info('Pool winner found! Playing: '.$playlist_video->title);
                $requester = 'POOL';
                $duration = $this->convertDurationToSeconds($video_details->duration);
                PlaylistState::updateOrInsert(
                    ['id' => 1],  // or some other logic if you have multiple states
                    [
                        'video_id'   => $video_id,
                        'start_time' => Carbon::now(),
                        'duration'   => $duration,
                        'updated_at' => Carbon::now(),
                        'requested_by' => $requester
                    ]
                );
                PlayListPool::truncate();
                return $duration;
            }
            else{
                PlaylistState::updateOrInsert(
                    ['id' => 1],  // or some other logic if you have multiple states
                    [
                        'video_id'   => $nextVideo['id'],
                        'start_time' => Carbon::now(),
                        'duration'   => $nextVideo['duration'],
                        'updated_at' => Carbon::now(),
                        'requested_by' => $requester
                    ]
                );
                $this->info("Rotated -> Next video: {$nextVideo['id']} (Duration: {$nextVideo['duration']}s)");
                return $nextVideo['duration'];
            }
        }
    }

    /**
     * Convert an ISO 8601 duration (e.g. "PT4M13S") to total seconds.
     * If your DB already stores integer seconds, you can skip this function.
     */
    protected function convertDurationToSeconds($duration): float|int
    {
        if (!$duration) {
            return 0;
        }

        // Typical format: "PT4M13S" or "PT1H2M5S"
        preg_match_all('/(\d+)([HMS])/', $duration, $matches, PREG_SET_ORDER);

        $seconds = 0;
        foreach ($matches as $match) {
            $value = (int) $match[1];
            $unit  = $match[2];

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
