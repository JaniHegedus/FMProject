<?php

namespace Database\Factories;

use App\Models\PlaylistVideo;
use App\Models\VideoData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VideoDataFactory extends Factory
{
    protected $model = VideoData::class;

    public function definition(): array
    {
        return [
            'description' => $this->faker->text(),
            'status' => $this->faker->word(),
            'duration' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'playlist_video_id' => PlaylistVideo::factory(),
        ];
    }
}
