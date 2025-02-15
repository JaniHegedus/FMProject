<?php

namespace Database\Factories;

use App\Models\Listener;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ListenerFactory extends Factory
{
    protected $model = Listener::class;

    public function definition(): array
    {
        return [
            'ip' => $this->faker->ipv4(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),

            'listening_time' => $this->faker->numberBetween(0, 3600), // random seconds between 0 and 3600
        ];
    }
}
