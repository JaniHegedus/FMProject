<?php

namespace Database\Seeders;

use App\Models\PlaylistState;
use Illuminate\Database\Seeder;

class PlaylistStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        PlaylistState::insert([
            'video_id' => 'INITIAL_VIDEO_ID', // Replace with the first video's ID
            'start_time' => now(),
        ]);
    }
}
