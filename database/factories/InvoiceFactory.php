<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'client_id'      => Client::factory(),
            'invoice_number' => 'INV-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status'         => InvoiceStatus::Draft,
            'issued_at'      => $issuedAt,
            'due_at'         => (clone $issuedAt)->modify('+30 days'),
            'paid_at'        => null,
            'notes'          => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => InvoiceStatus::Draft]);
    }

    public function sent(): static
    {
        return $this->state(['status' => InvoiceStatus::Sent]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'  => InvoiceStatus::Paid,
            'paid_at' => fake()->dateTimeBetween($attributes['issued_at'], 'now'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'    => InvoiceStatus::Overdue,
            'issued_at' => now()->subDays(60),
            'due_at'    => now()->subDays(30),
        ]);
    }
}
