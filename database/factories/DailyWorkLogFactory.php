<?php

namespace Database\Factories;

use App\Models\DailyWorkLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyWorkLog>
 */
class DailyWorkLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(7, 16);
        $start = sprintf('%02d:00', $startHour);
        $end = sprintf('%02d:00', $startHour + 1);

        return [
            'user_id' => User::factory(),
            'date' => fake()->date(),
            'start_time' => $start,
            'end_time' => $end,
            'activity' => fake()->sentence(3),
            'remarks' => fake()->optional()->sentence(),
            'proof_path' => null,
        ];
    }
}
