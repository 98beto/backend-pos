<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes that were missing from the initial migrations.
     *
     * Only sales.sale_date is added here — all FK columns (cash_session_id,
     * customer_id, sale_id, product_id) already have implicit indexes created
     * automatically by ->foreignId()->constrained() in their respective migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sale_date']);
        });
    }
};
