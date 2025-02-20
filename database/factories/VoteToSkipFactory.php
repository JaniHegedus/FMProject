<?php

namespace Database\Factories;

use App\Models\Listener;
use App\Models\VoteToSkip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VoteToSkipFactory extends Factory
{
    protected $model = VoteToSkip::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'listener_id' => Listener::factory(),
        ];
    }
}
