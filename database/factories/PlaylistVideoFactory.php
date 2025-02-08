<?php

namespace Database\Factories;

use App\Models\PlaylistState;
use App\Models\PlaylistVideo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlaylistVideoFactory extends Factory
{
    protected $model = PlaylistVideo::class;

    public function definition(): array
    {
        return [
            'video_id' => $this->faker->word(),
            'title' => $this->faker->word(),
            'published_at' => Carbon::now(),
            'thumbnail_url' => $this->faker->url(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'playlist_state_id' => PlaylistState::factory(),
        ];
    }
}
