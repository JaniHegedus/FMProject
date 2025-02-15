<?php

namespace App\Http\Controllers;

use App\Models\Listener;
use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\Factory;
use Illuminate\View\View;

class FMController extends Controller
{
    /**
     * Front Page Of Course
     * @return View|Factory|Application
     */
    public function index(): View|Factory|Application
    {
        try {
            // Fetch the current video and its start time
            $playlistState = PlaylistState::first();

            if (!$playlistState) {
                return view('fm.index', ['error' => 'No video is currently playing.']);
            }
            $playlistVideo = PlaylistVideo::where('video_id', $playlistState->video_id)->first();
            // Calculate the progress (in seconds) based on the server's current time
            $startTime = strtotime($playlistState->start_time);
            $currentTime = time();
            $progress = $currentTime - $startTime;
            $requesterString = '';
            if($playlistState->requested_by != '') $requesterString = 'Requested by: '.$playlistState->requested_by;
            return view('fm.index', [
                'videoId' => $playlistState->video_id,
                'videoTitle' => $playlistVideo->title,
                'startTime' => $playlistState->start_time,
                'progress' => $progress,
                'duration' => $playlistState->duration ?? 0, // Provide a fallback for duration
                'requester' => $requesterString,
            ]);
        } catch (Exception $e) {
            return view('fm.index', ['error' => $e->getMessage()]);
        }
    }

    /**
     * This is for querying the current Video for the synced listening.
     * @return JsonResponse
     */
    public function currentVideo(Request $request): JsonResponse
    {
        try {
            deleteInactiveUsers('Listeners');
            $userId = $request->query('user_id');
            $listener = Listener::where('user_id', $userId)
                ->where('ip', $request->query('ip'))
                ->first();

            if ($listener) {
                $inactiveSeconds = $listener->updated_at->diffInSeconds(now());
                if ($inactiveSeconds > 15) {
                    // Delete the listener if inactive for more than 15 seconds
                    $listener->delete();
                    $listening_time = 0;
                } else {
                    // Calculate new listening time:
                    // Add the time from the last update until now to the existing listening_time.
                    $elapsedSinceLastUpdate = $listener->updated_at->diffInSeconds(now());
                    $listening_time = $listener->listening_time + $elapsedSinceLastUpdate;
                }
            } else {
                $listening_time = 0;
            }

            // Then update or create the record
            Listener::updateOrCreate(
                [
                    'ip' => $request->query('ip'),
                ],
                [
                    'user_id' => $userId ?? null,
                    'listening_time' => $listening_time,
                    // updated_at will automatically be set to now() if you save the model,
                ]
            );

            $playlistState = PlaylistState::first();
            $playlistVideo = PlaylistVideo::where('video_id', $playlistState->video_id)->first();

            if (!$playlistState) {
                return returnJSONErrorMessage('No video is currently playing.');
            }

            $startTime = strtotime($playlistState->start_time);
            $currentTime = time();
            $progress = $currentTime - $startTime;

            return response()->json([
                'video_id' => $playlistState->video_id,
                'video_title' => $playlistVideo->title,
                'start_time' => $playlistState->start_time,
                'duration' => $playlistState->duration ?? 0,
                'progress' => $progress,
                'requester' => $playlistState->requested_by,
            ]);
        } catch (Exception $e) {
            return returnErrorJSON($e);
        }
    }
}
