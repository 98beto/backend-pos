<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Device;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'MATRIZ'],
            [
                'name' => 'Ferreteria Metropolis',
                'address' => null,
                'is_active' => true,
            ],
        );

        $devices = [
            [
                'name' => 'Caja 1',
                'identifier' => 'POS-01',
                'secret' => 'secret-123',
            ],
            [
                'name' => 'Caja 2',
                'identifier' => 'POS-02',
                'secret' => 'secret-456',
            ],
            [
                'name' => 'Almacen',
                'identifier' => 'ALMACEN-01',
                'secret' => 'secret-789',
            ],
        ];

        foreach ($devices as $device) {
            Device::updateOrCreate(
                ['identifier' => $device['identifier']],
                [
                    'branch_id' => $branch->id,
                    'name' => $device['name'],
                    'secret_hash' => Hash::make($device['secret']),
                    'is_active' => true,
                ],
            );
        }
    }
}
