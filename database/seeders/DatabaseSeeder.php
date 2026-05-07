<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaultBranch = Branch::firstOrCreate(
            ['code' => 'MATRIZ'],
            [
                'name' => 'Ferreteria Metropolis',
                'address' => null,
                'is_active' => true,
            ],
        );

        // ── 1. Categorías ──────────────────────────────────────────────────────
        $categories = collect([
            "Herramientas Manuales",
            "Herramientas Eléctricas",
            "Plomería",
            "Electricidad",
            "Pinturas y Acabados",
            "Construcción",
            "Tornillería y Fijaciones",
            "Adhesivos y Selladores",
        ])->map(fn($name) => Category::firstOrCreate(["name" => $name]));

        // ── 2. Marcas ──────────────────────────────────────────────────────────
        $brands = collect([
            "Stanley",
            "Truper",
            "Irwin",
            "Bosch",
            "DeWalt",
            "Makita",
            "3M",
            "Urrea",
            "Pretul",
            "Surtek",
        ])->map(fn($name) => Brand::firstOrCreate(["name" => $name]));

        // ── 3. Proveedores ─────────────────────────────────────────────────────
        Supplier::factory(6)->create();

        // ── 4. Clientes ────────────────────────────────────────────────────────
        Customer::factory(12)->create();

        // ── 5. Productos ───────────────────────────────────────────────────────
        // Catálogo con categoría y marca asignadas manualmente para coherencia
        $catalog = [
            // [nombre, sku, unidad, costo, precio, min_stock, stock, cat_index, brand_index]
            ["Martillo de uña 16 oz", "HM-001", "PZA", 85, 135, 5, 18, 0, 0],
            ['Desarmador plano 6"', "HM-002", "PZA", 28, 45, 10, 35, 0, 1],
            ["Desarmador Phillips #2", "HM-003", "PZA", 28, 45, 10, 30, 0, 1],
            ['Pinza de punta 8"', "HM-004", "PZA", 65, 105, 5, 22, 0, 7],
            ['Llave ajustable 10"', "HM-005", "PZA", 95, 155, 5, 15, 0, 7],
            ["Cinta métrica 5m", "HM-006", "PZA", 45, 75, 8, 3, 0, 0], // bajo stock
            ['Nivel de burbuja 24"', "HM-007", "PZA", 110, 180, 4, 8, 0, 1],
            ["Segueta con arco", "HM-008", "PZA", 55, 90, 5, 12, 0, 8],
            ['Taladro percutor 1/2"', "HE-001", "PZA", 850, 1350, 3, 5, 1, 3],
            [
                'Esmeriladora angular 4.5"',
                "HE-002",
                "PZA",
                650,
                1050,
                3,
                4,
                1,
                3,
            ],
            ['Sierra circular 7.25"', "HE-003", "PZA", 1100, 1750, 2, 2, 1, 5],
            [
                "Atornillador inalámbrico 12V",
                "HE-004",
                "PZA",
                780,
                1250,
                3,
                0,
                1,
                5,
            ], // sin stock
            ['Llave para tubos 14"', "PL-001", "PZA", 120, 195, 5, 10, 2, 7],
            ['Codo PVC 1/2" x 90°', "PL-002", "PZA", 4, 8, 50, 120, 2, 9],
            ['Tubo PVC 1/2" x 6m', "PL-003", "PZA", 55, 90, 10, 25, 2, 9],
            ['Llave de paso 1/2"', "PL-004", "PZA", 45, 75, 10, 18, 2, 9],
            ['Teflón rollo 3/4"', "PL-005", "PZA", 8, 15, 20, 2, 2, 9], // bajo stock
            [
                "Cable THW calibre 12 (m)",
                "EL-001",
                "MTS",
                12,
                22,
                50,
                200,
                3,
                null,
            ],
            [
                "Contacto doble polarizado",
                "EL-002",
                "PZA",
                35,
                58,
                15,
                45,
                3,
                null,
            ],
            ["Apagador sencillo", "EL-003", "PZA", 28, 48, 15, 38, 3, null],
            ["Foco LED 10W E27", "EL-004", "PZA", 28, 48, 20, 5, 3, null], // bajo stock
            ['Cinta aislante 3/4"', "EL-005", "PZA", 10, 18, 25, 60, 3, 6],
            [
                "Pintura vinílica blanca 4L",
                "PT-001",
                "PZA",
                180,
                285,
                5,
                12,
                4,
                null,
            ],
            [
                "Pintura esmalte negro 1L",
                "PT-002",
                "PZA",
                65,
                105,
                5,
                8,
                4,
                null,
            ],
            ['Brocha de 3"', "PT-003", "PZA", 25, 42, 10, 20, 4, null],
            ['Rodillo 9" con charola', "PT-004", "PZA", 45, 72, 8, 14, 4, null],
            [
                'Tornillo autoperforante 8x1"',
                "TR-001",
                "CTO",
                18,
                32,
                20,
                50,
                6,
                9,
            ],
            ['Taquete Fisher 5/16"', "TR-002", "CTO", 15, 28, 20, 40, 6, null],
            ['Clavo de acero 2.5"', "TR-003", "KG", 22, 38, 15, 30, 6, null],
            ["Cemento gris 50kg", "CN-001", "SAC", 185, 280, 10, 22, 5, null],
            [
                'Varilla corrugada 3/8" x 6m',
                "CN-002",
                "PZA",
                95,
                150,
                10,
                15,
                5,
                null,
            ],
            [
                'Malla electrosoldada 6x6"',
                "CN-003",
                "PZA",
                320,
                510,
                5,
                3,
                5,
                null,
            ], // bajo stock
            ["Silicón blanco 280ml", "AD-001", "PZA", 38, 62, 10, 25, 7, 6],
            ["Resistol 5000 500ml", "AD-002", "PZA", 42, 68, 8, 18, 7, null],
            [
                "Pegamento para PVC 250ml",
                "AD-003",
                "PZA",
                32,
                52,
                10,
                22,
                7,
                null,
            ],
        ];

        $products = collect($catalog)->map(function ($item) use (
            $categories,
            $brands,
            $defaultBranch,
        ) {
            [
                $name,
                $sku,
                $unit,
                $cost,
                $price,
                $minStock,
                $stock,
                $catIdx,
                $brandIdx,
            ] = $item;

            $product = Product::updateOrCreate([
                "sku" => $sku,
            ], [
                "name" => $name,
                "description" => null,
                "cost_price" => $cost,
                "unit_measure" => $unit,
                "category_id" => $categories[$catIdx]->id,
                "brand_id" => $brandIdx !== null ? $brands[$brandIdx]->id : null,
            ]);

            BranchProduct::updateOrCreate([
                'branch_id' => $defaultBranch->id,
                'product_id' => $product->id,
            ], [
                'price' => $price,
                'stock_quantity' => $stock,
                'min_stock' => $minStock,
                'is_available' => true,
            ]);

            return $product;
        });

        // ── 6. Movimientos de inventario ───────────────────────────────────────
        $movementsData = [
            // [product_sku, type, quantity, notes]
            ["HM-001", "in", 50, "Compra OC-2026-001"],
            ["HM-002", "in", 80, "Compra OC-2026-001"],
            ["HM-003", "in", 80, "Compra OC-2026-001"],
            ["HE-001", "in", 10, "Compra OC-2026-002"],
            ["HE-002", "in", 10, "Compra OC-2026-002"],
            ["HE-003", "in", 8, "Compra OC-2026-002"],
            ["HE-004", "in", 8, "Compra OC-2026-002"],
            ["PL-002", "in", 200, "Compra OC-2026-003"],
            ["PL-003", "in", 40, "Compra OC-2026-003"],
            ["EL-001", "in", 300, "Compra OC-2026-004"],
            ["TR-001", "in", 100, "Compra OC-2026-005"],
            ["CN-001", "in", 50, "Compra OC-2026-006"],
            ["HM-006", "out", 5, "Devolución a proveedor - mercancía dañada"],
            ["PL-005", "out", 10, "Merma registrada"],
            ["HE-004", "out", 8, "Devolución a proveedor"],
            [
                "HM-001",
                "adjustment",
                18,
                "Conteo físico - ajuste de inventario",
            ],
            ["EL-004", "adjustment", 5, "Ajuste por diferencia en conteo"],
            ["CN-003", "adjustment", 3, "Ajuste inventario mensual"],
        ];

        $productsBySkuMap = $products->keyBy("sku");

        foreach ($movementsData as [$sku, $type, $quantity, $notes]) {
            $product = $productsBySkuMap[$sku] ?? null;
            if (!$product) {
                continue;
            }

            InventoryMovement::updateOrCreate([
                "product_id" => $product->id,
                "branch_id" => $defaultBranch->id,
                "type" => $type,
                "quantity" => $quantity,
            ], [
                'source' => 'manual',
                'reference_id' => null,
                "notes" => $notes,
            ]);
        }

        $this->call(DeviceSeeder::class);
    }
}
