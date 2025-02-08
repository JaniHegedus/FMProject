<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FMController;
use App\Http\Controllers\PoolController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', [FMController::class, 'index']);

Route::get('/currentVideo', [FMController::class, 'currentVideo']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout',   [AuthController::class, 'logout']);

Route::get('/user', function (Request $request) {
    // This will return the currently logged in user, or null if no user is authenticated.
    return response()->json(Auth::user());
});
Route::post('/start-pool', [PoolController::class, 'startPool']);
Route::get('/search-song', [SearchController::class, 'searchSong']);
Route::get('/pool-status', [PoolController::class, 'getPoolStatus']);
