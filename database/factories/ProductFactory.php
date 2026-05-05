<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    // Catálogo de productos de ferretería con sus datos base
    private static array $catalog = [
        // Herramientas Manuales
        ['name' => 'Martillo de uña 16 oz',        'sku' => 'HM-001', 'unit' => 'PZA', 'cost' => 85,   'price' => 135,  'min_stock' => 5],
        ['name' => 'Desarmador plano 6"',           'sku' => 'HM-002', 'unit' => 'PZA', 'cost' => 28,   'price' => 45,   'min_stock' => 10],
        ['name' => 'Desarmador Phillips #2',         'sku' => 'HM-003', 'unit' => 'PZA', 'cost' => 28,   'price' => 45,   'min_stock' => 10],
        ['name' => 'Pinza de punta 8"',             'sku' => 'HM-004', 'unit' => 'PZA', 'cost' => 65,   'price' => 105,  'min_stock' => 5],
        ['name' => 'Llave ajustable 10"',           'sku' => 'HM-005', 'unit' => 'PZA', 'cost' => 95,   'price' => 155,  'min_stock' => 5],
        ['name' => 'Cinta métrica 5m',              'sku' => 'HM-006', 'unit' => 'PZA', 'cost' => 45,   'price' => 75,   'min_stock' => 8],
        ['name' => 'Nivel de burbuja 24"',          'sku' => 'HM-007', 'unit' => 'PZA', 'cost' => 110,  'price' => 180,  'min_stock' => 4],
        ['name' => 'Segueta con arco',              'sku' => 'HM-008', 'unit' => 'PZA', 'cost' => 55,   'price' => 90,   'min_stock' => 5],
        // Herramientas Eléctricas
        ['name' => 'Taladro percutor 1/2"',         'sku' => 'HE-001', 'unit' => 'PZA', 'cost' => 850,  'price' => 1350, 'min_stock' => 3],
        ['name' => 'Esmeriladora angular 4.5"',     'sku' => 'HE-002', 'unit' => 'PZA', 'cost' => 650,  'price' => 1050, 'min_stock' => 3],
        ['name' => 'Sierra circular 7.25"',         'sku' => 'HE-003', 'unit' => 'PZA', 'cost' => 1100, 'price' => 1750, 'min_stock' => 2],
        ['name' => 'Atornillador inalámbrico 12V',  'sku' => 'HE-004', 'unit' => 'PZA', 'cost' => 780,  'price' => 1250, 'min_stock' => 3],
        // Plomería
        ['name' => 'Llave para tubos 14"',          'sku' => 'PL-001', 'unit' => 'PZA', 'cost' => 120,  'price' => 195,  'min_stock' => 5],
        ['name' => 'Codo PVC 1/2" x 90°',          'sku' => 'PL-002', 'unit' => 'PZA', 'cost' => 4,    'price' => 8,    'min_stock' => 50],
        ['name' => 'Tubo PVC 1/2" x 6m',           'sku' => 'PL-003', 'unit' => 'PZA', 'cost' => 55,   'price' => 90,   'min_stock' => 10],
        ['name' => 'Llave de paso 1/2"',            'sku' => 'PL-004', 'unit' => 'PZA', 'cost' => 45,   'price' => 75,   'min_stock' => 10],
        ['name' => 'Teflón rollo 3/4"',             'sku' => 'PL-005', 'unit' => 'PZA', 'cost' => 8,    'price' => 15,   'min_stock' => 20],
        // Electricidad
        ['name' => 'Cable THW calibre 12 (m)',      'sku' => 'EL-001', 'unit' => 'MTS', 'cost' => 12,   'price' => 22,   'min_stock' => 50],
        ['name' => 'Contacto doble polarizado',     'sku' => 'EL-002', 'unit' => 'PZA', 'cost' => 35,   'price' => 58,   'min_stock' => 15],
        ['name' => 'Apagador sencillo',             'sku' => 'EL-003', 'unit' => 'PZA', 'cost' => 28,   'price' => 48,   'min_stock' => 15],
        ['name' => 'Foco LED 10W E27',              'sku' => 'EL-004', 'unit' => 'PZA', 'cost' => 28,   'price' => 48,   'min_stock' => 20],
        ['name' => 'Cinta aislante 3/4"',           'sku' => 'EL-005', 'unit' => 'PZA', 'cost' => 10,   'price' => 18,   'min_stock' => 25],
        // Pinturas
        ['name' => 'Pintura vinílica blanca 4L',    'sku' => 'PT-001', 'unit' => 'PZA', 'cost' => 180,  'price' => 285,  'min_stock' => 5],
        ['name' => 'Pintura esmalte negro 1L',      'sku' => 'PT-002', 'unit' => 'PZA', 'cost' => 65,   'price' => 105,  'min_stock' => 5],
        ['name' => 'Brocha de 3"',                  'sku' => 'PT-003', 'unit' => 'PZA', 'cost' => 25,   'price' => 42,   'min_stock' => 10],
        ['name' => 'Rodillo 9" con charola',        'sku' => 'PT-004', 'unit' => 'PZA', 'cost' => 45,   'price' => 72,   'min_stock' => 8],
        // Tornillería
        ['name' => 'Tornillo autoperforante 8x1"',  'sku' => 'TR-001', 'unit' => 'CTO', 'cost' => 18,   'price' => 32,   'min_stock' => 20],
        ['name' => 'Taquete Fisher 5/16"',          'sku' => 'TR-002', 'unit' => 'CTO', 'cost' => 15,   'price' => 28,   'min_stock' => 20],
        ['name' => 'Clavo de acero 2.5"',           'sku' => 'TR-003', 'unit' => 'KG',  'cost' => 22,   'price' => 38,   'min_stock' => 15],
        // Construcción
        ['name' => 'Cemento gris 50kg',             'sku' => 'CN-001', 'unit' => 'SAC', 'cost' => 185,  'price' => 280,  'min_stock' => 10],
        ['name' => 'Varilla corrugada 3/8" x 6m',  'sku' => 'CN-002', 'unit' => 'PZA', 'cost' => 95,   'price' => 150,  'min_stock' => 10],
        ['name' => 'Malla electrosoldada 6x6"',     'sku' => 'CN-003', 'unit' => 'PZA', 'cost' => 320,  'price' => 510,  'min_stock' => 5],
        // Adhesivos
        ['name' => 'Silicón blanco 280ml',          'sku' => 'AD-001', 'unit' => 'PZA', 'cost' => 38,   'price' => 62,   'min_stock' => 10],
        ['name' => 'Resistol 5000 500ml',           'sku' => 'AD-002', 'unit' => 'PZA', 'cost' => 42,   'price' => 68,   'min_stock' => 8],
        ['name' => 'Pegamento para PVC 250ml',      'sku' => 'AD-003', 'unit' => 'PZA', 'cost' => 32,   'price' => 52,   'min_stock' => 10],
    ];

    private static int $index = 0;

    public function definition(): array
    {
        $item = static::$catalog[static::$index % count(static::$catalog)];
        static::$index++;

        return [
            'name'          => $item['name'],
            'sku'           => $item['sku'],
            'description'   => null,
            'cost_price'    => $item['cost'],
            'price'         => $item['price'],
            'stock_quantity' => $this->faker->numberBetween(0, 80),
            'min_stock'     => $item['min_stock'],
            'unit_measure'  => $item['unit'],
            'barcode'       => null,
            'is_active'     => true,
            'category_id'   => null,
            'brand_id'      => null,
        ];
    }
}
