<?php

namespace App\Http\Controllers;

use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\View\Factory;
use Illuminate\View\View;

class FMController extends Controller
{
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

    public function currentVideo(): JsonResponse
    {
        try {
            $playlistState = PlaylistState::first();
            $playlistVideo = PlaylistVideo::where('video_id', $playlistState->video_id)->first();

            if (!$playlistState) {
                return response()->json([
                    'error' => 'No video is currently playing.',
                ]);
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
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }
}
