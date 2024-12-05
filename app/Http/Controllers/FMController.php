<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class FMController extends Controller
{
    public function index()
    {
        try {
            // Fetch the current video and its start time
            $playlistState = DB::table('playlist_state')->first();

            if (!$playlistState) {
                return view('fm.index', ['error' => 'No video is currently playing.']);
            }

            // Calculate the progress (in seconds) based on the server's current time
            $startTime = strtotime($playlistState->start_time);
            $currentTime = time();
            $progress = $currentTime - $startTime;

            return view('fm.index', [
                'videoId' => $playlistState->video_id,
                'videoTitle' => "Currently Playing Video ID: " . $playlistState->video_id,
                'startTime' => $playlistState->start_time,
                'progress' => $progress,
                'duration' => $playlistState->duration ?? 0, // Provide a fallback for duration
            ]);
        } catch (\Exception $e) {
            return view('fm.index', ['error' => $e->getMessage()]);
        }
    }

    public function currentVideo()
    {
        try {
            $playlistState = DB::table('playlist_state')->first();

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
                'video_title' => "Currently Playing Video ID: " . $playlistState->video_id,
                'start_time' => $playlistState->start_time,
                'duration' => $playlistState->duration ?? 0,
                'progress' => $progress,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }
}
