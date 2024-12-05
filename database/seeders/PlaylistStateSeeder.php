<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaylistStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('playlist_state')->insert([
            'video_id' => 'INITIAL_VIDEO_ID', // Replace with the first video's ID
            'start_time' => now(),
        ]);
    }
}
