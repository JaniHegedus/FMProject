<?php

/**
 * Convert an ISO 8601 duration (e.g., "PT4M13S") to total seconds.
 */

use App\Models\ChatUser;
use App\Models\History;
use App\Models\Listener;
use App\Models\PlayListPool;
use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use App\Models\User;
use App\Models\VideoData;
use App\Models\VoteToSkip;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * This is for Converting String based Duration to seconds.
 * Usual inputted format: PT2M39S
 * Output is second.
 * @param string $duration
 * @return float|int
 */
function convertDurationToSeconds(string $duration): float|int
{
    if (!$duration) {
        return 0;
    }

    // Typical format: "PT4M13S" or "PT1H2M5S"
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
/**
 * This is for returning a catched Error as a JSON
 * @param Exception $e
 * @param int $errorCode
 * @return JsonResponse
 */
function returnErrorJSON(Exception $e, int $errorCode = 500) : JSONResponse
{
    return response()->json([
        'error' => $e->getMessage(),
    ], $errorCode);
}

/**
 * This is for entry returning as a JSON and also handling the empty array.
 * @param $entries
 * @param string $emptyMessage
 * @return JsonResponse
 */
function returnEntriesAsJSON($entries, string $emptyMessage): JsonResponse
{
    if($entries->isEmpty()) {
        return response()->json([
            'empty'   => true,
            'message' => $emptyMessage,
        ]);
    }
    return response()->json([
        'empty'   => false,
        'entries' => $entries,
    ]);
}

/**
 * This is for returning an error message only in JSON format.
 * @param $message
 * @param int $code
 * @return JsonResponse
 */
function returnJSONErrorMessage($message, int $code = 500): JsonResponse
{
    return response()->json([
        'error' => $message,
    ], $code);
}
/**
 * @param $type
 * @return void
 */
function deleteInactiveUsers($type): bool
{
    $users = [];
    switch ($type) {
        case 'ChatUsers':
            $users = ChatUser::all();
            $interval = 2;
            break;
        case 'Listeners':
            $users = Listener::all();
            $interval = 5;
            break;
        default:
            break;
    }
    if(!$users->isEmpty()) {
        foreach ($users as $user) {
            if($user->updated_at->diffInSeconds(now()) > $interval){
                try{
                    $user->delete();
                }catch(Exception $e){
                    return false;
                }
            }
        }
        return true;
    }
    return false;
}
function changeSong($currentVideo, $requester, $nextVideo){
    VoteToSkip::truncate();
    $poolwinner = PlaylistPool::where('created_at', '<=', Carbon::now()->subMinutes(10))
        ->orderBy('votes', 'desc')
        ->first();

    if($poolwinner){
        $video_id = $poolwinner->video_id;
        $playlist_video = PlaylistVideo::where('video_id',$video_id)->first();
        $video_details = VideoData::where('playlist_video_id',$playlist_video->id)->first();
        $requester = User::where('id',$poolwinner->created_by)->first()->name ?? 'Pool';
        $duration = convertDurationToSeconds($video_details->duration);
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
        addToHistory($currentVideo);
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
        addToHistory($currentVideo);
        return $nextVideo['duration'];
    }
}

/**
 * @param PlaylistState $currentVideo
 * @return void
 */
function addToHistory(PlaylistState $currentVideo): void
{
    $playlistVideo = PlaylistVideo::where('video_id',$currentVideo->video_id)->first();
    History::create([
        'playlist_video_id' => $playlistVideo->id,
        'played_at' => $currentVideo->start_time
    ]);
}
