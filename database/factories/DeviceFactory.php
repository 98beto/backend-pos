<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        $identifier = strtoupper($this->faker->unique()->bothify('POS-##??'));

        return [
            'branch_id' => Branch::factory(),
            'name' => $identifier,
            'identifier' => $identifier,
            'secret_hash' => Hash::make('secret-123'),
            'is_active' => true,
            'last_login_at' => null,
        ];
    }
}
