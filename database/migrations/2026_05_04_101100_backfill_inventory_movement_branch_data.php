<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultBranchId = DB::table('branches')->where('code', 'MATRIZ')->value('id');

        if (! $defaultBranchId) {
            return;
        }

        DB::table('inventory_movements')
            ->whereNull('branch_id')
            ->update(['branch_id' => $defaultBranchId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inventory_movements')->update(['branch_id' => null]);
    }
};
