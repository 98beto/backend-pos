<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('cash_sessions', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable(false)->change();
                $table->string('device_identifier')->nullable(false)->change();
            });

            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable(false)->change();
            });

            Schema::table('saved_carts', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable(false)->change();
            });

            DB::statement("CREATE UNIQUE INDEX cash_sessions_open_branch_device_unique ON cash_sessions (branch_id, device_identifier) WHERE status = 'open'");

            return;
        }

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->unique(['branch_id', 'device_identifier', 'status'], 'cash_sessions_branch_device_status_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS cash_sessions_open_branch_device_unique');

            Schema::table('saved_carts', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->change();
            });

            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->change();
            });

            Schema::table('cash_sessions', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->change();
                $table->string('device_identifier')->nullable()->change();
            });

            return;
        }

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropUnique('cash_sessions_branch_device_status_unique');
        });
    }
};
