<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\PlaylistVideo;

class SearchController extends Controller
{
    public function searchSong(Request $request): JsonResponse
    {
        // Validate that a search query is provided
        $request->validate([
            'query' => 'required|string'
        ]);

        $query = $request->input('query');

        // Search the PlaylistVideo model for titles that contain the query string
        $results = PlaylistVideo::where('title', 'LIKE', '%' . $query . '%')
            ->get(['video_id', 'title']);

        // Return the matching results as JSON
        return response()->json([
            'results' => $results,
        ]);
    }
}

