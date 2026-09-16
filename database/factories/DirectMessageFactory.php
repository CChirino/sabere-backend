<?php

namespace Database\Factories;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DirectMessageFactory extends Factory
{
    protected $model = DirectMessage::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'subject' => $this->faker->optional()->sentence(),
            'body' => $this->faker->paragraph(),
            'read_at' => null,
        ];
    }
}
