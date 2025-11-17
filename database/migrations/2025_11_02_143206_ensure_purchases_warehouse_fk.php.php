<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('purchases')) return;

        // Tambah kolom jika belum ada
        if (!Schema::hasColumn('purchases', 'warehouse_id')) {
            Schema::table('purchases', function (Blueprint $t) {
                $t->unsignedBigInteger('warehouse_id')->nullable()->index()->after('supplier_id');
            });
        }

        // Tambah FK (abaikan error jika sudah ada)
        Schema::table('purchases', function (Blueprint $t) {
            try {
                $t->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onUpdate('restrict')->onDelete('set null');
            } catch (\Throwable $e) {
                // FK sudah ada → aman diabaikan
            }
        });
    }

    public function down(): void
    {
        // Biasanya tidak perlu rollback destruktif.
        // Jika perlu, bisa drop FK/column manual sesuai kebutuhan.
    }
};
