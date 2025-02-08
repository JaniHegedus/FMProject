<?php

namespace Database\Factories;

use App\Models\PlaylistState;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlaylistStateFactory extends Factory
{
    protected $model = PlaylistState::class;

    public function definition(): array
    {
        return [
            'video_id' => $this->faker->word(),
            'start_time' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'duration' => $this->faker->randomNumber(),
            'requested_by' => $this->faker->word(),
        ];
    }
}
