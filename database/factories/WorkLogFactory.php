<?php

namespace Database\Factories;

use App\Enums\WorkLogStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkLog>
 */
class WorkLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id'   => Client::factory(),
            'invoice_id'  => null,
            'description' => fake()->sentence(5),
            'hours'       => fake()->randomFloat(2, 0.5, 8),
            'rate'        => fake()->numberBetween(8000, 15000),
            'worked_at'   => fake()->dateTimeBetween('-30 days', 'now'),
            'status'      => WorkLogStatus::Unbilled,
        ];
    }

    public function unbilled(): static
    {
        return $this->state([
            'status'     => WorkLogStatus::Unbilled,
            'invoice_id' => null,
        ]);
    }

    public function billed(): static
    {
        return $this->state(['status' => WorkLogStatus::Billed]);
    }
}
