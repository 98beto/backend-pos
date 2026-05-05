<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaultBranch = Branch::where('code', 'MATRIZ')->firstOrFail();

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
        ])->map(fn($name) => Category::create(["name" => $name]));

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
        ])->map(fn($name) => Brand::create(["name" => $name]));

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

            return Product::create([
                "name" => $name,
                "sku" => $sku,
                "description" => null,
                "cost_price" => $cost,
                "price" => $price,
                "stock_quantity" => $stock,
                "min_stock" => $minStock,
                "unit_measure" => $unit,
                "barcode" => null,
                "is_active" => true,
                "category_id" => $categories[$catIdx]->id,
                "brand_id" =>
                    $brandIdx !== null ? $brands[$brandIdx]->id : null,
            ]);
        });

        // // ── 6. Sesiones de caja ────────────────────────────────────────────────
        // // 4 sesiones cerradas en días anteriores
        // $closedSessions = collect([
        //     ["-6 days", 500, 2850.5],
        //     ["-5 days", 500, 3120.0],
        //     ["-4 days", 500, 1980.75],
        //     ["-3 days", 500, 4250.0],
        // ])->map(function ($data) {
        //     [$daysAgo, $opening, $closing] = $data;
        //     return CashSession::create([
        //         "status" => "closed",
        //         "opening_balance" => $opening,
        //         "closing_balance" => $closing,
        //         "opened_at" => now()->modify($daysAgo)->setTime(8, 0),
        //         "closed_at" => now()->modify($daysAgo)->setTime(18, 0),
        //         "notes" => null,
        //     ]);
        // });

        // 1 sesión abierta hoy
        // $openSession = CashSession::create([
        //     'status'          => 'open',
        //     'opening_balance' => 500.00,
        //     'closing_balance' => null,
        //     'opened_at'       => now()->setTime(8, 0),
        //     'closed_at'       => null,
        //     'notes'           => 'Turno apertura',
        // ]);

        // $allSessions = $closedSessions->push($openSession);

        // ── 7. Ventas con detalles ─────────────────────────────────────────────
        $customers = Customer::all();

        // // Generar 30 ventas distribuidas entre sesiones
        // $salesData = [
        //     // [session_index, customer_index_or_null, payment, items_count, days_ago]
        //     [0, 0, "cash", 2, 6],
        //     [0, null, "cash", 1, 6],
        //     [0, 1, "card", 3, 6],
        //     [0, null, "cash", 2, 6],
        //     [0, 2, "cash", 1, 6],
        //     [0, 9, "transfer", 4, 6],
        //     [1, null, "cash", 2, 5],
        //     [1, 3, "card", 1, 5],
        //     [1, null, "cash", 3, 5],
        //     [1, 4, "cash", 2, 5],
        //     [1, 10, "transfer", 5, 5],
        //     [1, null, "cash", 1, 5],
        //     [2, 5, "cash", 2, 4],
        //     [2, null, "cash", 1, 4],
        //     [2, 6, "card", 3, 4],
        //     [2, null, "cash", 2, 4],
        //     [3, null, "cash", 1, 3],
        //     [3, 7, "cash", 4, 3],
        //     [3, 11, "transfer", 2, 3],
        //     [3, null, "card", 1, 3],
        //     [3, 8, "cash", 3, 3],
        //     [3, null, "cash", 2, 3],
        //     // Ventas de hoy (sesión abierta)
        //     [4, null, "cash", 1, 0],
        //     [4, 0, "cash", 2, 0],
        //     [4, 1, "card", 1, 0],
        //     [4, null, "cash", 3, 0],
        //     [4, 3, "cash", 2, 0],
        //     [4, null, "transfer", 1, 0],
        //     [4, 2, "cash", 1, 0],
        //     [4, null, "cash", 2, 0],
        // ];

        // // Índices de productos "vendibles" (con stock suficiente)
        // $sellableIndexes = $products
        //     ->filter(fn($p) => $p->stock_quantity >= 5)
        //     ->values();

        // foreach ($salesData as $sd) {
        //     [$sessionIdx, $custIdx, $payment, $itemCount, $daysAgo] = $sd;

        //     $session = $allSessions[$sessionIdx];
        //     $customerId =
        //         $custIdx !== null
        //             ? $customers[$custIdx % $customers->count()]->id
        //             : null;

        //     // Construir items
        //     $items = [];
        //     $pickedIds = [];
        //     for ($i = 0; $i < $itemCount; $i++) {
        //         // Elegir producto que no se repita en la misma venta
        //         $product = $sellableIndexes
        //             ->filter(fn($p) => !in_array($p->id, $pickedIds))
        //             ->random();
        //         $pickedIds[] = $product->id;

        //         $qty = rand(1, 3);
        //         $subtotal = round($qty * $product->price, 2);

        //         $items[] = [
        //             "product_id" => $product->id,
        //             "quantity" => $qty,
        //             "unit_price" => $product->price,
        //             "tax_amount" => 0,
        //             "subtotal" => $subtotal,
        //             "total" => $subtotal,
        //         ];
        //     }

        //     $totalSubtotal = collect($items)->sum("subtotal");
        //     $discount = $sessionIdx % 5 === 0 ? 0 : 0; // sin descuentos para simplicidad

        //     $saleDate =
        //         $daysAgo === 0
        //             ? now()->subMinutes(rand(10, 240))
        //             : now()
        //                 ->modify("-{$daysAgo} days")
        //                 ->setTime(rand(9, 17), rand(0, 59));

        //     $sale = Sale::create([
        //         "customer_id" => $customerId,
        //         "cash_session_id" => $session->id,
        //         "payment_method" => $payment,
        //         "subtotal" => $totalSubtotal,
        //         "tax_amount" => 0,
        //         "discount_amount" => $discount,
        //         "total_amount" => $totalSubtotal,
        //         "status" => "completed",
        //         "sale_date" => $saleDate,
        //     ]);

        //     foreach ($items as $item) {
        //         SaleDetail::create(
        //             array_merge($item, ["sale_id" => $sale->id]),
        //         );
        //     }
        // }

        // ── 8. Movimientos de inventario ───────────────────────────────────────
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

            InventoryMovement::create([
                "product_id" => $product->id,
                "branch_id" => $defaultBranch->id,
                "type" => $type,
                "quantity" => $quantity,
                "notes" => $notes,
            ]);
        }
    }
}
