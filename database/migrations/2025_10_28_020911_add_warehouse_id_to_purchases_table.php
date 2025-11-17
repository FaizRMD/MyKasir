<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $t) {
            if (!Schema::hasColumn('purchase_items','qty_received')) {
                $t->integer('qty_received')->default(0)->after('qty');
            }
            if (!Schema::hasColumn('purchase_items','discount')) {
                $t->decimal('discount', 14, 2)->default(0)->after('cost');
            }
            if (!Schema::hasColumn('purchase_items','tax_pct')) {
                $t->decimal('tax_pct', 5, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('purchase_items','subtotal')) {
                $t->decimal('subtotal', 14, 2)->default(0)->after('tax_pct');
            }
        });

        // Tambah index HANYA kalau belum ada
        $this->addIndexIfMissing('purchase_items', 'purchase_id', 'purchase_items_purchase_id_index');
        $this->addIndexIfMissing('purchase_items', 'product_id',  'purchase_items_product_id_index');
    }

    public function down(): void
    {
        // Jangan drop kolom biar data aman. Kalau mau rapihin index, drop kalau ada.
        $this->dropIndexIfExists('purchase_items', 'purchase_items_purchase_id_index');
        $this->dropIndexIfExists('purchase_items', 'purchase_items_product_id_index');
    }

    /** Helpers */
    private function addIndexIfMissing(string $table, string $column, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $t) use ($column, $indexName) {
                $t->index($column, $indexName);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $res = DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$db, $table, $indexName]
        );
        return !empty($res);
    }
};
