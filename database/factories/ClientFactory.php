<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'          => fake()->company(),
            'email'         => fake()->unique()->companyEmail(),
            'address'       => fake()->address(),
            'default_rate'  => fake()->numberBetween(8000, 15000),
            'payment_terms' => fake()->randomElement([14, 21, 30]),
            'notes'         => null,
        ];
    }

    public function acme(): static
    {
        return $this->state([
            'name'          => 'Acme Corp',
            'email'         => 'billing@acme.com',
            'address'       => '123 Main St, New York, NY 10001',
            'default_rate'  => 12000,
            'payment_terms' => 30,
            'notes'         => 'Large enterprise client. Tends to pay 2-3 weeks late. Always pays eventually.',
        ]);
    }

    public function brightStudio(): static
    {
        return $this->state([
            'name'          => 'Bright Studio',
            'email'         => 'hello@brightstudio.io',
            'address'       => '45 Creative Lane, Austin, TX 78701',
            'default_rate'  => 9500,
            'payment_terms' => 14,
            'notes'         => 'Design agency. Fast payer, clear briefs.',
        ]);
    }

    public function novaHealth(): static
    {
        return $this->state([
            'name'          => 'Nova Health',
            'email'         => 'accounts@novahealth.com',
            'address'       => '789 Wellness Blvd, San Francisco, CA 94105',
            'default_rate'  => 15000,
            'payment_terms' => 30,
            'notes'         => 'Healthcare startup. Requires formal invoices with detailed line items.',
        ]);
    }

    public function techStart(): static
    {
        return $this->state([
            'name'          => 'TechStart Ltd',
            'email'         => 'finance@techstart.dev',
            'address'       => '12 Startup Row, Seattle, WA 98101',
            'default_rate'  => 11000,
            'payment_terms' => 21,
            'notes'         => null,
        ]);
    }
}
