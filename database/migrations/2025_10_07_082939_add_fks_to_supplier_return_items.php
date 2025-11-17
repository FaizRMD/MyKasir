<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cek apakah sebuah FK constraint ada pada tabel.
     */
    private function fkExists(string $table, string $constraint): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $constraint]);

        return (int)($row->c ?? 0) > 0;
    }

    /**
     * Cek apakah sebuah index ada pada tabel.
     */
    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ", [$table, $index]);

        return (int)($row->c ?? 0) > 0;
    }

    public function up(): void
    {
        /**
         * supplier_return_items
         */
        if (Schema::hasTable('supplier_return_items')) {
            // Drop FK lama (default/eksplisit) agar tidak bentrok
            foreach ([
                'supplier_return_items_supplier_return_id_foreign',
                'supplier_return_items_purchase_item_id_foreign',
                'supplier_return_items_product_id_foreign',
                'sritems_supplier_return_id_fk',
                'sritems_grn_item_id_fk',
                'sritems_purchase_item_id_fk',
                'sritems_product_id_fk',
            ] as $fk) {
                if ($this->fkExists('supplier_return_items', $fk)) {
                    DB::statement("ALTER TABLE `supplier_return_items` DROP FOREIGN KEY `$fk`");
                }
            }

            Schema::table('supplier_return_items', function (Blueprint $table) {
                // index bantu (cek dulu biar gak 1061)
                if (!app(__CLASS__)->indexExists('supplier_return_items', 'sritems_supplier_return_id_idx')) {
                    $table->index('supplier_return_id', 'sritems_supplier_return_id_idx');
                }
                if (Schema::hasColumn('supplier_return_items', 'goods_receipt_item_id')
                    && !app(__CLASS__)->indexExists('supplier_return_items', 'sritems_grn_item_id_idx')) {
                    $table->index('goods_receipt_item_id', 'sritems_grn_item_id_idx');
                }
                if (!app(__CLASS__)->indexExists('supplier_return_items', 'sritems_purchase_item_id_idx')) {
                    $table->index('purchase_item_id', 'sritems_purchase_item_id_idx');
                }
                if (!app(__CLASS__)->indexExists('supplier_return_items', 'sritems_product_id_idx')) {
                    $table->index('product_id', 'sritems_product_id_idx');
                }

                // FK baru bernama eksplisit
                if (Schema::hasColumn('supplier_return_items', 'supplier_return_id') && Schema::hasTable('supplier_returns')) {
                    $table->foreign('supplier_return_id', 'sritems_supplier_return_id_fk')
                          ->references('id')->on('supplier_returns')
                          ->cascadeOnDelete();
                }

                if (Schema::hasColumn('supplier_return_items', 'goods_receipt_item_id') && Schema::hasTable('goods_receipt_items')) {
                    $table->foreign('goods_receipt_item_id', 'sritems_grn_item_id_fk')
                          ->references('id')->on('goods_receipt_items')
                          ->restrictOnDelete();
                }

                if (Schema::hasColumn('supplier_return_items', 'purchase_item_id') && Schema::hasTable('purchase_items')) {
                    DB::statement("ALTER TABLE `supplier_return_items` MODIFY `purchase_item_id` BIGINT UNSIGNED NULL");
                    $table->foreign('purchase_item_id', 'sritems_purchase_item_id_fk')
                          ->references('id')->on('purchase_items')
                          ->nullOnDelete();
                }

                if (Schema::hasColumn('supplier_return_items', 'product_id') && Schema::hasTable('products')) {
                    $table->foreign('product_id', 'sritems_product_id_fk')
                          ->references('id')->on('products')
                          ->restrictOnDelete();
                }
            });
        }

        /**
         * purchase_items
         */
        if (Schema::hasTable('purchase_items')) {
            // Drop FK lama (kalau ada)
            foreach ([
                'purchase_items_purchase_id_foreign',
                'purchase_items_product_id_foreign',
                'purchase_items_purchase_id_fk',
                'purchase_items_product_id_fk',
            ] as $fk) {
                if ($this->fkExists('purchase_items', $fk)) {
                    DB::statement("ALTER TABLE `purchase_items` DROP FOREIGN KEY `$fk`");
                }
            }

            // Tambah index hanya jika belum ada
            Schema::table('purchase_items', function (Blueprint $table) {
                if (!app(__CLASS__)->indexExists('purchase_items', 'pitems_purchase_id_idx')) {
                    $table->index('purchase_id', 'pitems_purchase_id_idx');
                }
                if (!app(__CLASS__)->indexExists('purchase_items', 'pitems_product_id_idx')) {
                    $table->index('product_id', 'pitems_product_id_idx');
                }

                // Recreate FK dengan aturan yang sehat
                if (Schema::hasTable('purchases') && Schema::hasColumn('purchase_items', 'purchase_id')) {
                    $table->foreign('purchase_id', 'purchase_items_purchase_id_fk')
                          ->references('id')->on('purchases')
                          ->cascadeOnDelete();
                }
                if (Schema::hasTable('products') && Schema::hasColumn('purchase_items', 'product_id')) {
                    $table->foreign('product_id', 'purchase_items_product_id_fk')
                          ->references('id')->on('products')
                          ->restrictOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        // supplier_return_items: drop FK baru
        foreach ([
            'sritems_supplier_return_id_fk',
            'sritems_grn_item_id_fk',
            'sritems_purchase_item_id_fk',
            'sritems_product_id_fk',
        ] as $fk) {
            if ($this->fkExists('supplier_return_items', $fk)) {
                DB::statement("ALTER TABLE `supplier_return_items` DROP FOREIGN KEY `$fk`");
            }
        }

        // drop index dengan guard
        if (Schema::hasTable('supplier_return_items')) {
            Schema::table('supplier_return_items', function (Blueprint $table) {
                foreach ([
                    'sritems_supplier_return_id_idx',
                    'sritems_grn_item_id_idx',
                    'sritems_purchase_item_id_idx',
                    'sritems_product_id_idx',
                ] as $idx) {
                    try { $table->dropIndex($idx); } catch (\Throwable $e) {}
                }
            });
        }

        // purchase_items: drop FK
        foreach ([
            'purchase_items_purchase_id_fk',
            'purchase_items_product_id_fk',
        ] as $fk) {
            if ($this->fkExists('purchase_items', $fk)) {
                DB::statement("ALTER TABLE `purchase_items` DROP FOREIGN KEY `$fk`");
            }
        }

        // purchase_items: drop index dengan guard
        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                foreach ([
                    'pitems_purchase_id_idx',
                    'pitems_product_id_idx',
                ] as $idx) {
                    try { $table->dropIndex($idx); } catch (\Throwable $e) {}
                }
            });
        }
    }
};
