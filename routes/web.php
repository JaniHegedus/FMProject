<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Services\YouTubeService;
use Illuminate\Http\Request;

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
Route::get('/currentVideo', [App\Http\Controllers\FMController::class, 'currentVideo']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout',   [AuthController::class, 'logout']);

Route::get('/user', function (Request $request) {
    // This will return the currently logged in user, or null if no user is authenticated.
    return response()->json(Auth::user());
});
