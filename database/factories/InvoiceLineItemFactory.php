<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceLineItem>
 */
class InvoiceLineItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id'  => Invoice::factory(),
            'description' => fake()->sentence(4),
            'quantity'    => fake()->randomElement([1, 2, 4, 8, 16]),
            'unit_price'  => fake()->numberBetween(5000, 50000),
        ];
    }
}
