<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\PlaylistVideo;
use App\Models\VideoData;
use Exception;
use Illuminate\Http\JsonResponse;

class HistoryController extends Controller
{
    /**
     * Returns a time controlled JSON of the History table entries.
     * @param $startDate
     * @param $endDate
     * @return JsonResponse
     */
    public function getHistoryBetween($startDate, $endDate = null ){
        if(!$endDate) $endDate = now();
        try {
            $history = History::whereBetween('played_at', [$startDate, $endDate])->get();
            $this->returnHistory($history);
            return $this->returnHistory($history);

        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
     * Returns the complete History table contents
     * @return JsonResponse
     */
    public function getFullHistory(){
        try {
            $history = History::get();
            return $this->returnHistory($history);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
     * This does the Detail getting for an entry in the history array
     * with modifying the contents
     * adding description and title
     * @param $history
     * @return JsonResponse
     */
    private function returnHistory(&$history) : JSONResponse
    {
        foreach ($history as $entry) {
            $video = PlaylistVideo::where('id',$entry->playlist_video_id)->first();
            $details = VideoData::where('playlist_video_id', $video->id)->first();
            $entry->video_description = $details?->description;
            $entry->video_title = $video?->title;
        }
        return returnEntriesAsJSON($history,'The history is empty.');
    }
}
