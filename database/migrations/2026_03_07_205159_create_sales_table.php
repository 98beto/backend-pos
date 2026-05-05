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
        Schema::create("sales", function (Blueprint $table) {
            $table->id();
            $table->timestamp("sale_date")->useCurrent();

            $table->foreignId("customer_id")->nullable()->constrained();
            $table->foreignId("cash_session_id")->constrained();

            $table->decimal("tax_amount", 10, 2)->default(0);
            $table->decimal("subtotal", 10, 2);
            $table->decimal("discount_amount", 10, 2)->default(0);
            $table->decimal("total_amount", 10, 2);

            $table->string("payment_method");
            $table->string("status")->default("completed");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sales");
    }
};
