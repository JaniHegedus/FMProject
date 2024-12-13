<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use React\EventLoop\Factory;

// Bootstrap Laravel manually for the database connection
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Initialize Eloquent Capsule for database operations
$capsule = new Capsule();
$capsule->addConnection(config('database.connections.sqlite'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Get the ReactPHP event loop
$loop = Factory::create();
// Function to rotate the playlist
function rotatePlaylist()
{
    $youtube = new \App\Services\YouTubeService();

    // Fetch playlist videos
    $response = $youtube->getPlaylistVideos();
    $videos = $response['items'] ?? [];

    if (empty($videos)) {
        echo "The playlist is empty.\n";
        return null;
    }

    // Extract video IDs from playlist items
    $videoIds = array_filter(array_map(function ($video) {
        return $video['snippet']['resourceId']['videoId'] ?? null;
    }, $videos));

    if (empty($videoIds)) {
        echo "No valid videos found in the playlist.\n";
        return null;
    }

    // Fetch details (including status) for all video IDs
    $videoDetailsResponse = $youtube->getVideoDetails($videoIds);
    $videoDetails = $videoDetailsResponse['items'] ?? [];

    // Filter out videos that aren't publicly available and embeddable
    $videoData = [];
    foreach ($videoDetails as $videoDetail) {
        if (empty($videoDetail['status']) || empty($videoDetail['contentDetails'])) {
            continue;
        }
        #echo json_encode($videoDetail['status'])."\r\n";

        if (isset($videoDetail['contentDetails']['contentRating']['ytRating']) &&
            $videoDetail['contentDetails']['contentRating']['ytRating'] === 'ytAgeRestricted') {
            continue;
        }
        if (isset($videoDetail['snippet']['liveBroadcastContent']) &&
            $videoDetail['snippet']['liveBroadcastContent'] !== 'none') {
            continue;
        }


        // Check if public
        if (!isset($videoDetail['status']['privacyStatus']) || $videoDetail['status']['privacyStatus'] !== 'public') {
            continue;
        }

        // Check if embeddable
        if (isset($videoDetail['status']['embeddable']) && $videoDetail['status']['embeddable'] === false) {
            continue;
        }

        // Check region restrictions
        $region = 'HU';
        $regionRestriction = $videoDetail['contentDetails']['regionRestriction'] ?? null;
        if ($regionRestriction) {
            if (isset($regionRestriction['blocked']) && in_array($region, $regionRestriction['blocked'])) {
                continue;
            }

            if (isset($regionRestriction['allowed']) && !in_array($region, $regionRestriction['allowed'])) {
                continue;
            }
        }

        // Check duration
        if (empty($videoDetail['contentDetails']['duration'])) {
            continue;
        }

        $durationInSeconds = convertDurationToSeconds($videoDetail['contentDetails']['duration']);
        if (!$durationInSeconds) {
            continue;
        }

        // If we get here, the video should be good to go
        $videoData[] = [
            'id' => $videoDetail['id'],
            'duration' => $durationInSeconds,
        ];
    }


    if (empty($videoData)) {
        echo "No valid playable videos found in the playlist.\n";
        return null;
    }

    // Get the current video from the database
    $currentVideo = Capsule::table('playlist_state')->first();

    // Find current video index
    $currentIndex = $currentVideo ? array_search($currentVideo->video_id, array_column($videoData, 'id')) : false;

    // Determine the next index
    if ($currentIndex === false) {
        echo "Current video not in playlist, resetting to first.\n";
        $nextIndex = 0;
    } else {
        $nextIndex = ($currentIndex + 1) % count($videoData);
    }

    $nextVideo = $videoData[$nextIndex];

    // Update the playlist state in the database
    Capsule::table('playlist_state')->updateOrInsert(
        ['id' => 1],
        [
            'video_id' => $nextVideo['id'],
            'start_time' => Carbon::now(),
            'duration' => $nextVideo['duration'],
            'updated_at' => Carbon::now(),
        ]
    );

    echo "Playlist rotated successfully. Next video: {$nextVideo['id']} (Duration: {$nextVideo['duration']}s)\n";

    return $nextVideo['duration'];
}


// Schedule the rotation with ReactPHP
function scheduleRotation($loop)
{
    $duration = rotatePlaylist();

    if ($duration) {
        $loop->addTimer($duration, function () use ($loop) {
            scheduleRotation($loop); // Reschedule after the duration ends
        });
    } else {
        echo "No valid duration found. Rescheduling in 10 seconds.\n";
        $loop->addTimer(10, function () use ($loop) {
            scheduleRotation($loop); // Retry after 10 seconds
        });
    }
}

// Start the scheduling process
scheduleRotation($loop);

// Run the ReactPHP event loop
$loop->run();

// Utility function to convert ISO 8601 duration to seconds
function convertDurationToSeconds($duration)
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

    return $seconds ?: null;
}
