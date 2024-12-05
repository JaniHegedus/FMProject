<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Fetch the current video and its duration
        $currentVideo = DB::table('playlist_state')->first();

        if ($currentVideo && $currentVideo->duration) {
            $durationInMinutes = ceil($currentVideo->duration / 60); // Convert seconds to minutes, rounding up

            $schedule->command('playlist:rotate')
                ->everyMinute()
                ->skip(function () use ($currentVideo) {
                    // Skip if the current video's time hasn't passed yet
                    $startTime = strtotime($currentVideo->start_time);
                    $currentTime = now()->timestamp;
                    $elapsedTime = $currentTime - $startTime;

                    return $elapsedTime < $currentVideo->duration;
                })
                ->description('Rotate the playlist when the current video ends');
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
