<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashSession>
 */
class CashSessionFactory extends Factory
{
    protected $model = CashSession::class;

    public function definition(): array
    {
        // Default: closed session in the past
        $openedAt = $this->faker->dateTimeBetween('-30 days', '-1 day');
        $closedAt = (clone $openedAt)->modify('+8 hours');

        return [
            'branch_id' => null,
            'device_id' => Device::factory(),
            'status'          => 'closed',
            'opening_balance' => 500.00,
            'closing_balance' => $this->faker->randomFloat(2, 800, 3000),
            'opened_at'       => $openedAt,
            'closed_at'       => $closedAt,
            'notes'           => null,
        ];
    }

    /**
     * State: currently open session (no closing data).
     */
    public function open(): static
    {
        return $this->state(fn () => [
            'status' => 'open',
            'opening_balance' => 500.00,
            'closing_balance' => null,
            'opened_at' => now()->setTime(8, 0),
            'closed_at' => null,
            'notes' => 'Turno apertura',
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CashSession $cashSession) {
            if ($cashSession->device_id && ! $cashSession->branch_id) {
                $cashSession->branch_id = Device::find($cashSession->device_id)?->branch_id;
            }
        })->afterCreating(function (CashSession $cashSession) {
            if (! $cashSession->branch_id && $cashSession->device_id) {
                $cashSession->update([
                    'branch_id' => $cashSession->device?->branch_id,
                ]);
            }
        });
    }
}
