<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    /** Cek apakah sebuah index dengan nama tertentu sudah ada (MySQL) */
    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') return false;
        $rows = DB::select("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND INDEX_NAME   = ?
            LIMIT 1
        ", [$table, $indexName]);
        return !empty($rows);
    }

    /** Cek apakah FK pada kolom tertentu sudah ada (MySQL) */
    private function fkExists(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') return false;
        $rows = DB::select("
            SELECT 1
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME    = ?
              AND COLUMN_NAME   = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$table, $column]);
        return !empty($rows);
    }

    public function up(): void
    {
        if (!Schema::hasTable('pembelian_items')) return;

        // Tambah FK product_id -> products(id) hanya jika belum ada
        if (Schema::hasColumn('pembelian_items','product_id') && !$this->fkExists('pembelian_items','product_id')) {
            Schema::table('pembelian_items', function (Blueprint $t) {
                $t->foreign('product_id')
                  ->references('id')->on('products')
                  ->onUpdate('restrict')->onDelete('restrict');
            });
        }

        // Tambah index komposit HANYA jika belum ada (cek nama index default Laravel)
        if (!$this->indexExists('pembelian_items', 'pembelian_items_pembelian_id_product_id_index')) {
            Schema::table('pembelian_items', function (Blueprint $t) {
                $t->index(['pembelian_id','product_id'], 'pembelian_items_pembelian_id_product_id_index');
            });
        }

        if (!$this->indexExists('pembelian_items', 'pembelian_items_product_id_batch_no_index')) {
            Schema::table('pembelian_items', function (Blueprint $t) {
                $t->index(['product_id','batch_no'], 'pembelian_items_product_id_batch_no_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pembelian_items')) return;

        // Drop index hanya jika ada (aman dijalankan berulang)
        if ($this->indexExists('pembelian_items', 'pembelian_items_pembelian_id_product_id_index')) {
            Schema::table('pembelian_items', function (Blueprint $t) {
                $t->dropIndex('pembelian_items_pembelian_id_product_id_index');
            });
        }
        if ($this->indexExists('pembelian_items', 'pembelian_items_product_id_batch_no_index')) {
            Schema::table('pembelian_items', function (Blueprint $t) {
                $t->dropIndex('pembelian_items_product_id_batch_no_index');
            });
        }

        // (opsional) $t->dropForeign(['product_id']);
    }
};
