<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    private function fkExists(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') return false;
        $rows = DB::select("
            SELECT 1
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$table, $column]);
        return !empty($rows);
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() !== 'mysql') return false;
        $rows = DB::select("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1
        ", [$table, $index]);
        return !empty($rows);
    }

    public function up(): void
    {
        if (!Schema::hasTable('pembelian')) return;

        // Pastikan warehouse_id nullable (MySQL tanpa dbal)
        if (Schema::hasColumn('pembelian','warehouse_id') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `pembelian` MODIFY `warehouse_id` BIGINT UNSIGNED NULL");
        }

        // FK supplier_id
        if (Schema::hasColumn('pembelian','supplier_id') && !$this->fkExists('pembelian','supplier_id')) {
            Schema::table('pembelian', function (Blueprint $t) {
                try {
                    $t->foreign('supplier_id')->references('id')->on('suppliers')
                      ->onUpdate('restrict')->onDelete('restrict');
                } catch (\Throwable $e) { /* ignore */ }
            });
        }

        // FK warehouse_id (SET NULL)
        if (Schema::hasColumn('pembelian','warehouse_id') && !$this->fkExists('pembelian','warehouse_id')) {
            Schema::table('pembelian', function (Blueprint $t) {
                try {
                    $t->foreign('warehouse_id')->references('id')->on('warehouses')
                      ->onUpdate('restrict')->onDelete('set null');
                } catch (\Throwable $e) { /* ignore */ }
            });
        }

        // Index/Unique komposit (cek dulu)
        Schema::table('pembelian', function (Blueprint $t) {
            try { $t->unique(['supplier_id','invoice_no'], 'uq_pembelian_supplier_invoice'); } catch (\Throwable $e) {}
            try { $t->index(['supplier_id','invoice_date'],  'ix_pembelian_supplier_date'); }  catch (\Throwable $e) {}
            try { $t->index(['warehouse_id','invoice_date'], 'ix_pembelian_wh_date'); }       catch (\Throwable $e) {}
            try { $t->index(['invoice_date','payment_type'], 'ix_pembelian_date_paytype'); }  catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pembelian')) return;

        Schema::table('pembelian', function (Blueprint $t) {
            // Aman: drop index/unique jika perlu; abaikan jika tidak ada
            try { $t->dropUnique('uq_pembelian_supplier_invoice'); } catch (\Throwable $e) {}
            try { $t->dropIndex('ix_pembelian_supplier_date'); }     catch (\Throwable $e) {}
            try { $t->dropIndex('ix_pembelian_wh_date'); }           catch (\Throwable $e) {}
            try { $t->dropIndex('ix_pembelian_date_paytype'); }      catch (\Throwable $e) {}
            // FK dibiarkan (tidak destruktif)
        });
    }
};
