<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Services\YouTubeService;

Route::get('/', [App\Http\Controllers\FMController::class, 'index']);

Route::get('/test-youtube', function (YouTubeService $youtube) {
    $playlistId = 'YOUR_PLAYLIST_ID';
    try {
        $videos = $youtube->getPlaylistVideos($playlistId);
        return response()->json($videos);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
Route::get('/api/current-video', function () {
    $currentVideo = DB::table('playlist_state')->first();
    return response()->json($currentVideo);
});
