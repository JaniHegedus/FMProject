<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FMController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PoolController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

#Home Page
Route::get('/', [FMController::class, 'index']);

#Currently Playing Video
Route::get('/currentVideo', [FMController::class, 'currentVideo']);

#User
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout',   [AuthController::class, 'logout']);
Route::get('/user', function (Request $request) {
    // This will return the currently logged in user, or null if no user is authenticated.
    return response()->json(Auth::user());
});

#Pool
Route::post('/start-pool', [PoolController::class, 'startPool']);
Route::get('/search-song', [SearchController::class, 'searchSong']);
Route::get('/pool-status', [PoolController::class, 'getPoolStatus']);

#History
Route::get('/full-history', [HistoryController::class, 'getFullHistory']);
Route::get('/history/{startDate}/{endDate?}', [HistoryController::class, 'getHistoryBetween']);

#Messages
Route::get('/all-messages', [MessageController::class, 'getAllMessages']);
Route::get('/messages/{startDate}/{endDate?}', [MessageController::class, 'getMessagesBetween']);
Route::post('/send-message', [MessageController::class, 'sendMessage']);
Route::get('/messages-count/{startDate}/{endDate?}', [MessageController::class, 'messagesCount']);
