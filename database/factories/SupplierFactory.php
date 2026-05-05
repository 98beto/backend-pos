<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    private static array $suppliers = [
        [
            'name'           => 'Distribuidora Truper S.A. de C.V.',
            'contact_person' => 'Gerardo Mondragón',
            'email'          => 'ventas@truper-dist.com',
            'phone'          => '555-2001',
            'address'        => 'Blvd. Industrial 1200, CDMX',
            'credit_days'    => 30,
            'bank_info'      => 'BBVA / CLABE: 012180015800000001',
        ],
        [
            'name'           => 'Herramientas Stanley México',
            'contact_person' => 'Sofía Ríos',
            'email'          => 'srios@stanley.com.mx',
            'phone'          => '555-2002',
            'address'        => 'Av. Tecnología 450, Monterrey',
            'credit_days'    => 45,
            'bank_info'      => 'Banamex / CLABE: 002180700000000002',
        ],
        [
            'name'           => 'Materiales de Construcción del Norte',
            'contact_person' => 'Héctor Fuentes',
            'email'          => 'hfuentes@matnorte.com',
            'phone'          => '555-2003',
            'address'        => 'Carretera 57 km 12, Querétaro',
            'credit_days'    => 15,
            'bank_info'      => null,
        ],
        [
            'name'           => 'Eléctricos y Más S.A.',
            'contact_person' => 'Daniela Vega',
            'email'          => 'dvega@electricos.com',
            'phone'          => '555-2004',
            'address'        => 'Calle Reforma 88, Guadalajara',
            'credit_days'    => 30,
            'bank_info'      => 'Santander / CLABE: 014180000000000004',
        ],
        [
            'name'           => 'Pinturas Comex Mayoreo',
            'contact_person' => 'Luis Aranda',
            'email'          => 'laranda@comex-mayoreo.com',
            'phone'          => '555-2005',
            'address'        => 'Periférico Norte 3300, CDMX',
            'credit_days'    => 0,
            'bank_info'      => 'HSBC / CLABE: 021180000000000005',
        ],
        [
            'name'           => 'Plomería Industrial JM',
            'contact_person' => 'Jorge Medina',
            'email'          => null,
            'phone'          => '555-2006',
            'address'        => 'Calle Juárez 22, León GTO',
            'credit_days'    => 0,
            'bank_info'      => null,
        ],
    ];

    private static int $index = 0;

    public function definition(): array
    {
        $data = static::$suppliers[static::$index % count(static::$suppliers)];
        static::$index++;

        return $data;
    }
}
