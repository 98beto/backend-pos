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
        $branchId = DB::table('branches')->insertGetId([
            'name' => 'Matriz',
            'code' => 'MATRIZ',
            'address' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cash_sessions')->whereNull('branch_id')->update([
            'branch_id' => $branchId,
            'device_identifier' => DB::raw("COALESCE(device_identifier, 'legacy-device')"),
        ]);

        DB::table('sales')->whereNull('branch_id')->update([
            'branch_id' => $branchId,
        ]);

        DB::table('saved_carts')->whereNull('branch_id')->update([
            'branch_id' => $branchId,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('saved_carts')->update(['branch_id' => null]);
        DB::table('sales')->update(['branch_id' => null]);
        DB::table('cash_sessions')->update([
            'branch_id' => null,
            'device_identifier' => null,
        ]);
        DB::table('branches')->where('code', 'MATRIZ')->delete();
    }
};
