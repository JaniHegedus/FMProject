<?php

namespace App\Console\Commands;

use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlayVideo extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'play {title : The title of the video to search for} {name : The name of the person requesting the change}';

    /**
     * The console command description.
     */
    protected $description = 'Search for a video by title, insert it into playlist_state, and start playing it';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get the video title from the input argument
        $title = $this->argument('title');
        $name = $this->argument('name');

        // Fetch video details by joining playlist_videos and video_datas
        $videos = DB::table('playlist_videos')
            ->join('video_datas', 'playlist_videos.id', '=', 'video_datas.playlist_video_id')
            ->where('playlist_videos.title', 'like', '%' . $title . '%') // Match title
            ->select(
                'playlist_videos.video_id',
                'playlist_videos.title',
                'video_datas.duration'
            )
            ->get();


        if ($videos->isEmpty()) {
            $this->error("No video found with title: {$title}");
            return 1;
        }

        // If more than one video is found, ask the user to choose
        if ($videos->count() > 1) {
            $this->info("Multiple videos found:");
            foreach ($videos as $index => $video) {
                $this->info(($index + 1) . ") " . $video->title . " (Video ID: " . $video->video_id . ")");
            }

            $choice = $this->ask("Enter the number of the video to play (1-{$videos->count()}):");

            if (!is_numeric($choice) || $choice < 1 || $choice > $videos->count()) {
                $this->error("Invalid choice.");
                return 1;
            }

            $video = $videos[$choice - 1];
        } else {
            $video = $videos->first();
        }

        // Convert ISO 8601 duration to seconds
        $durationInSeconds = convertDurationToSeconds($video->duration);

        if ($durationInSeconds <= 0) {
            $this->error("Invalid duration for video: {$video->title}");
            return 1;
        }

        // Insert or update the record in the playlist_state table
        PlaylistState::updateOrInsert(
            ['id' => 1], // Assuming only one playlist_state record
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
        $this->info("Video ID: {$video->video_id}");
        $this->info("Duration: {$durationInSeconds} seconds");

        return 0;
    }

}
