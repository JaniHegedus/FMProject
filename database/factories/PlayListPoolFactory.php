<?php

namespace Database\Factories;

use App\Models\PlayListPool;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlayListPoolFactory extends Factory
{
    protected $model = PlayListPool::class;

    public function definition(): array
    {
        return [
            'video_id' => $this->faker->word(),
            'created_by' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'votes' => $this->faker->randomNumber(),
            'voted_by' => $this->faker->words(),
        ];
    }
}
