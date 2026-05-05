<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saved_carts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('cash_session_id')->nullable()->constrained();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('status')->default('saved');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('saved_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_cart_items');
        Schema::dropIfExists('saved_carts');
    }
};
