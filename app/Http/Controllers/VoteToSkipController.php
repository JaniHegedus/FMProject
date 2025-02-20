<?php

namespace App\Http\Controllers;

use App\Models\Listener;
use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use App\Models\VoteToSkip;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelIdea\Helper\App\Models\_IH_VoteToSkip_C;

class VoteToSkipController extends Controller
{
    /**
     * @return VoteToSkip[]|Collection|_IH_VoteToSkip_C
     */
    public function index()
    {
        return VoteToSkip::all();
    }
    public function voteSkip(Request $request) : JsonResponse
    {
        $noShuffle = true;
        $ip = $request->query('ip');
        try{
            $currentVideo = PlaylistState::first();
            if (!$currentVideo) {
                return returnJSONErrorMessage('No video is currently playing.');
            }
            $listener = Listener::where('ip',$ip)->first();
            if(!$listener) return returnJSONErrorMessage('No listener found, you might not be listening.');
            VoteToSkip::updateOrCreate(['listener_id' => $listener->id]);
            $votes = VoteToSkip::all()->count() ?? 0;

            $listenersCount = Listener::all()->count();
            if(($listenersCount/2) < $votes){
                $currentVideo = PlaylistState::first();
                $requester = 'Vote Skip';
                $playlistVideos = PlaylistVideo::with('videoDatas')->get();
                if ($playlistVideos->isEmpty()) {
                    return returnJSONErrorMessage('No videos in playlist_videos table.');
                }
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
                    $seconds     = convertDurationToSeconds($isoDuration);

                    if ($seconds > 0) {
                        $videoData[] = [
                            'id'       => $pv->video_id,
                            'duration' => $seconds,
                        ];
                    }
                }
                $currentIndex = false;
                if ($currentVideo) {
                    $currentIndex = array_search($currentVideo->video_id, array_column($videoData, 'id'));
                }

                // Step 5: Determine the next index
                if ($currentIndex === false) {
                    $nextIndex = 0;
                } else {
                    if($noShuffle) $nextIndex = ($currentIndex + 1) % count($videoData);
                    else $nextIndex = rand(0, count($videoData) - 1);
                }

                // Step 6: Update playlist_state with the next video
                $nextVideo = $videoData[$nextIndex];
                changeSong($currentVideo, $requester, $nextVideo);
            }

            return response()->json([
                'Votes' => $votes
            ]);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }
    public function getSkippersCount()
    {
        try{
            return response()->json([
                'skippers' => VoteToSkip::all()->count()
            ]);
        }catch (Exception $e){
            return returnJSONErrorMessage($e);
        }
    }
    /**
     * @param Request $request
     * @return VoteToSkip
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'listener_id' => ['required', 'exists:listeners'],
        ]);

        return VoteToSkip::create($data);
    }

    /**
     * @param VoteToSkip $voteToSkip
     * @return VoteToSkip
     */
    public function show(VoteToSkip $voteToSkip)
    {
        return $voteToSkip;
    }

    /**
     * @param Request $request
     * @param VoteToSkip $voteToSkip
     * @return VoteToSkip
     */
    public function update(Request $request, VoteToSkip $voteToSkip)
    {
        $data = $request->validate([
            'listener_id' => ['required', 'exists:listeners'],
        ]);

        $voteToSkip->update($data);

        return $voteToSkip;
    }

    /**
     * @param VoteToSkip $voteToSkip
     * @return JsonResponse
     */
    public function destroy(VoteToSkip $voteToSkip)
    {
        $voteToSkip->delete();

        return response()->json();
    }
}
