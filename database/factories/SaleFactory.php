<?php

namespace Database\Factories;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $device = Device::factory()->create();
        $cashSession = CashSession::factory()->open()->create([
            'branch_id' => $device->branch_id,
            'device_id' => $device->id,
        ]);

        return [
            'customer_id'     => $this->faker->boolean(60) ? Customer::inRandomOrder()->first()?->id : null,
            'cash_session_id' => $cashSession->id,
            'branch_id'       => $device->branch_id,
            'payment_method'  => $this->faker->randomElement(['cash', 'cash', 'cash', 'card', 'transfer']),
            'subtotal'        => 0,
            'tax_amount'      => 0,
            'discount_amount' => $this->faker->randomElement([0, 0, 0, 5, 10, 20]),
            'total_amount'    => 0,
            'status'          => 'completed',
            'sale_date'       => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
