<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("sale_details", function (Blueprint $table) {
            $table->id();
            $table->foreignId("sale_id")->constrained()->onDelete("cascade");
            $table->foreignId("product_id")->constrained();

            $table->integer("quantity");
            $table->decimal("unit_price", 10, 2); // Precio al que se vendió
            $table->decimal("tax_amount", 10, 2); // Impuesto aplicado a ESTA línea
            $table->decimal("subtotal", 10, 2); // (Cantidad * Precio) sin impuestos
            $table->decimal("total", 10, 2); // (Subtotal + Impuestos)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sale_details");
    }
};
