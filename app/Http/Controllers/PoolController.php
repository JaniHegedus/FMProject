<?php

namespace App\Http\Controllers;

use App\Models\PlayListPool;
use App\Models\VideoData;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // Make sure this is the correct Request import
use Illuminate\Support\Facades\Auth;
use App\Models\PlaylistVideo;

class PoolController extends Controller
{
    public function index()
    {
        return PlayListPool::all();
    }
    /**
     * This method is for starting a pool, also used to add a song to the pool.
     * @param Request $request
     * @return JsonResponse
     */
    public function startPool(Request $request): JsonResponse
    {
        // Validate the request using the request instance.
        $request->validate([
            'video_id' => 'required|string',
        ]);

        try {
            // Check if the video exists in the PlaylistVideo table.
            $playlistVideo = PlaylistVideo::where('video_id', $request->input('video_id'))->first();
            if (!$playlistVideo) {
                return returnJSONErrorMessage('Video not found in playlist.',404);
            }

            $video_id = $request->input('video_id');
            $currentUserId = Auth::user()->id;

            // Check if this video_id already exists in the pool table.
            $existingEntry = PlayListPool::where('video_id', $video_id)->first();

            if ($existingEntry) {
                // Decode the voted_by column (assumed to be stored as JSON).
                $votedBy = json_decode($existingEntry->voted_by, true) ?? Auth::user()->id;
                if (!is_array($votedBy)) {
                    $votedBy = [];
                }

                if (!in_array($currentUserId, $votedBy)) {
                    // If the current user hasn't voted yet, add their id to the array and increment votes.
                    $votedBy[] = $currentUserId;
                    PlayListPool::where('video_id', $video_id)->update([
                        'votes'      => $existingEntry->votes + 1,
                        'voted_by'   => json_encode($votedBy),
                        'updated_at' => now(),
                    ]);
                    $message = 'Vote added successfully.';
                } else {
                    // Optionally, you can choose to return a message that the user already voted.
                    $message = 'You have already voted for this video.';
                }
            } else {
                // Create a new entry with votes set to 1 and voted_by containing the current user id.
                PlayListPool::create([
                    'video_id'   => $video_id,
                    'votes'      => 1,
                    'voted_by'   => json_encode([$currentUserId]),
                    'created_by' => $currentUserId,
                ]);
                $message = 'Pool started successfully.';
            }

            return response()->json([
                'message'     => $message,
                'video_id'    => $video_id,
                'video_title' => $playlistVideo->title,
            ]);
        } catch (Exception $e) {
            return returnErrorJSON($e);
        }
    }

    /**
     * This is for getting the current status of the current pool.
     * @return JsonResponse
     */
    public function getPoolStatus(): JsonResponse
    {
        try {
            $poolEntries = PlayListPool::get();
            foreach ($poolEntries as $entry) {
                $video = PlaylistVideo::where('video_id', $entry->video_id)->first();
                $details = VideoData::where('playlist_video_id', $video->id)->first();
                $entry->video_description = $details?->description;
                $entry->video_title = $video?->title;
            }
            return returnEntriesAsJSON($poolEntries, 'The pool is empty.');
        } catch (Exception $e) {
            return returnErrorJSON($e);
        }
    }
}
