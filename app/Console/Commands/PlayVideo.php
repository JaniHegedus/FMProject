<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlayVideo extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'play {title : The title of the video to search for} {name : The name of the person requesting the change} {choice? : The index of the video to play, if multiple are found}';

    /**
     * The console command description.
     */
    protected $description = 'Search for a video by title, insert it into playlist_state, and start playing it';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $title = $this->argument('title');
        $name = $this->argument('name');
        $choice = $this->argument('choice');

        $videos = DB::table('playlist_videos')
            ->join('video_datas', 'playlist_videos.id', '=', 'video_datas.playlist_video_id')
            ->where('playlist_videos.title', 'like', '%' . $title . '%')
            ->select('playlist_videos.video_id', 'playlist_videos.title', 'video_datas.duration')
            ->get();

        if ($videos->isEmpty()) {
            $this->error("No video found with title: {$title}");
            return 1;
        }

        if ($videos->count() > 1 && is_null($choice)) {
            $this->error("Multiple videos found. Please specify a choice (1-{$videos->count()}).");
            return 1;
        }

        // Resolve the video
        $video = $videos->count() > 1 ? $videos[$choice - 1] : $videos->first();

        $durationInSeconds = $this->convertDurationToSeconds($video->duration);

        DB::table('playlist_state')->updateOrInsert(
            ['id' => 1],
            [
                'video_id'   => $video->video_id,
                'start_time' => Carbon::now(),
                'duration'   => $durationInSeconds,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'requested_by' => $name,
            ]
        );

        $this->info("Playing video: {$video->title}");
        return 0;
    }

    /**
     * Convert an ISO 8601 duration (e.g., "PT4M13S") to total seconds.
     */
    protected function convertDurationToSeconds($duration)
    {
        if (!$duration) {
            return 0;
        }

        // Match the duration format (e.g., "PT4M13S" or "PT1H2M5S")
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
