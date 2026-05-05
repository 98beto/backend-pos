<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    private static array $customers = [
        ['name' => 'Juan Pérez López',       'phone' => '555-1001', 'email' => 'juan.perez@gmail.com',    'tax_id' => 'PELJ800101ABC'],
        ['name' => 'María García Hernández', 'phone' => '555-1002', 'email' => 'maria.garcia@hotmail.com', 'tax_id' => null],
        ['name' => 'Carlos Ramírez Torres',  'phone' => '555-1003', 'email' => null,                       'tax_id' => 'RATC750615XYZ'],
        ['name' => 'Ana Martínez Ruiz',      'phone' => '555-1004', 'email' => 'ana.martinez@gmail.com',   'tax_id' => null],
        ['name' => 'Roberto Sánchez Díaz',   'phone' => '555-1005', 'email' => null,                       'tax_id' => null],
        ['name' => 'Laura Flores Mendoza',   'phone' => '555-1006', 'email' => 'laura.flores@outlook.com', 'tax_id' => 'FOML900320DEF'],
        ['name' => 'Miguel Ángel Vázquez',   'phone' => '555-1007', 'email' => null,                       'tax_id' => null],
        ['name' => 'Patricia Cruz Morales',  'phone' => '555-1008', 'email' => 'pcruz@empresa.com',        'tax_id' => 'CRMP850704GHI'],
        ['name' => 'José Luis Romero',       'phone' => '555-1009', 'email' => null,                       'tax_id' => null],
        ['name' => 'Construcciones Álvarez', 'phone' => '555-1010', 'email' => 'compras@calvarez.com',     'tax_id' => 'CAL920101JKL'],
        ['name' => 'Ferreblock S.A.',        'phone' => '555-1011', 'email' => 'ferreblock@gmail.com',     'tax_id' => 'FEB010301MNO'],
        ['name' => 'Obras y Proyectos GDL',  'phone' => '555-1012', 'email' => 'admin@opsgdl.com',         'tax_id' => 'OPG180601PQR'],
    ];

    private static int $index = 0;

    public function definition(): array
    {
        $data = static::$customers[static::$index % count(static::$customers)];
        static::$index++;

        return [
            'name'    => $data['name'],
            'phone'   => $data['phone'],
            'email'   => $data['email'],
            'address' => $this->faker->optional(0.5)->address(),
            'tax_id'  => $data['tax_id'],
        ];
    }
}
