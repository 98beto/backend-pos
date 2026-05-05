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
        Schema::create("products", function (Blueprint $table) {
            $table->id();

            $table->string("name");
            $table->text("description")->nullable();

            $table->decimal("cost_price", 10, 2)->nullable();
            $table->decimal("price", 10, 2);

            $table->integer("stock_quantity")->default(0);
            $table->integer("min_stock")->default(5);
            $table->string("unit_measure", 20)->default("PZA");

            $table->string("barcode")->unique()->nullable();
            $table->string("sku")->unique()->nullable();

            $table->boolean("is_active")->default(false);

            $table->foreignId("category_id")->nullable()->constrained();
            $table->foreignId("brand_id")->nullable()->constrained();

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("products");
    }
};
