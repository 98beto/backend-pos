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
        Schema::table('saved_carts', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('cash_session_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
