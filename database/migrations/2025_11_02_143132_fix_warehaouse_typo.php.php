<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Rename table 'warehaouse' -> 'warehouses' kalau memang ada
        if (Schema::hasTable('warehaouse') && !Schema::hasTable('warehouses')) {
            // Schema::rename tidak butuh dbal
            Schema::rename('warehaouse', 'warehouses');
        }

        // purchases.warehaouse_id -> purchases.warehouse_id (MySQL tanpa dbal)
        if (Schema::hasTable('purchases')
            && Schema::hasColumn('purchases', 'warehaouse_id')
            && !Schema::hasColumn('purchases', 'warehouse_id')) {

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `purchases` CHANGE `warehaouse_id` `warehouse_id` BIGINT UNSIGNED NULL");
            } else {
                // fallback aman: tambahkan kolom baru
                Schema::table('purchases', function (Blueprint $t) {
                    $t->unsignedBigInteger('warehouse_id')->nullable()->index();
                });
            }
        }

        // pembelian.warehaouse_id -> pembelian.warehouse_id (MySQL tanpa dbal)
        if (Schema::hasTable('pembelian')
            && Schema::hasColumn('pembelian', 'warehaouse_id')
            && !Schema::hasColumn('pembelian', 'warehouse_id')) {

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `pembelian` CHANGE `warehaouse_id` `warehouse_id` BIGINT UNSIGNED NULL");
            } else {
                Schema::table('pembelian', function (Blueprint $t) {
                    $t->unsignedBigInteger('warehouse_id')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        // Balikkan kalau perlu (jarang dipakai)
        if (Schema::hasTable('purchases')
            && Schema::hasColumn('purchases', 'warehouse_id')
            && !Schema::hasColumn('purchases', 'warehaouse_id')
            && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `purchases` CHANGE `warehouse_id` `warehaouse_id` BIGINT UNSIGNED NULL");
        }

        if (Schema::hasTable('pembelian')
            && Schema::hasColumn('pembelian', 'warehouse_id')
            && !Schema::hasColumn('pembelian', 'warehaouse_id')
            && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `pembelian` CHANGE `warehouse_id` `warehaouse_id` BIGINT UNSIGNED NULL");
        }

        if (Schema::hasTable('warehouses') && !Schema::hasTable('warehaouse')) {
            Schema::rename('warehouses', 'warehaouse');
        }
    }
};
