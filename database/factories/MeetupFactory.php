<?php

namespace Database\Factories;

use App\Models\Meetup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meetup>
 */
class MeetupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'starts_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'capacity' => fake()->numberBetween(5, 50),
            'requires_approval' => false,
        ];
    }
}
