<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ===== purchases: tambah kolom status (hanya jika tabelnya ada & kolom belum ada) =====
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'status')) {
            Schema::table('purchases', function (Blueprint $t) {
                $t->string('status', 32)->default('draft')->after('note');
            });
            // index status (aman jika index belum ada)
            try {
                Schema::table('purchases', fn(Blueprint $t) => $t->index('status', 'purchases_status_index'));
            } catch (\Throwable $e) { /* abaikan */ }
        }

        // ===== purchase_items: tambah qty_received (hanya jika tabel ada & kolom belum ada) =====
        if (Schema::hasTable('purchase_items') && !Schema::hasColumn('purchase_items', 'qty_received')) {
            Schema::table('purchase_items', function (Blueprint $t) {
                $t->unsignedInteger('qty_received')->default(0)->after('qty');
            });
        }

        // ===== goods_receipts (header) =====
        if (!Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $t) {
                // pastikan InnoDB (umumnya default Laravel sudah InnoDB)
                if (property_exists($t, 'engine')) $t->engine = 'InnoDB';

                $t->id();

                // kolom FK dibuat sebagai foreignId, tapi FKs akan ditambahkan KONDISIONAL setelah create
                $t->unsignedBigInteger('purchase_id');
                $t->unsignedBigInteger('supplier_id');

                $t->date('received_at');
                $t->string('grn_no')->nullable()->unique();
                $t->text('notes')->nullable();

                $t->timestamps();

                $t->index('received_at', 'goods_receipts_received_at_index');
                $t->index('supplier_id', 'goods_receipts_supplier_id_index');
            });

            // Tambah FK kondisional (hindari error 1146/1824)
            if (Schema::hasTable('purchases')) {
                Schema::table('goods_receipts', function (Blueprint $t) {
                    try {
                        $t->foreign('purchase_id', 'goods_receipts_purchase_id_fk')
                          ->references('id')->on('purchases')
                          ->cascadeOnDelete();
                    } catch (\Throwable $e) { /* abaikan */ }
                });
            }
            if (Schema::hasTable('suppliers')) {
                Schema::table('goods_receipts', function (Blueprint $t) {
                    try {
                        $t->foreign('supplier_id', 'goods_receipts_supplier_id_fk')
                          ->references('id')->on('suppliers')
                          ->restrictOnDelete();
                    } catch (\Throwable $e) { /* abaikan */ }
                });
            }
        }

        // ===== goods_receipt_items (detail) =====
        if (!Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $t) {
                if (property_exists($t, 'engine')) $t->engine = 'InnoDB';

                $t->id();

                $t->unsignedBigInteger('goods_receipt_id');
                $t->unsignedBigInteger('purchase_item_id');
                $t->unsignedBigInteger('product_id');

                $t->unsignedInteger('qty');
                $t->decimal('price', 14, 2)->default(0);
                $t->string('batch_no')->nullable();
                $t->date('exp_date')->nullable();
                $t->string('rack')->nullable();

                $t->timestamps();

                $t->index('product_id', 'gri_product_id_index');
                $t->index('batch_no', 'gri_batch_no_index');

                // cegah duplikasi kombinasi penting pada satu GR
                $t->unique(
                    ['goods_receipt_id','purchase_item_id','batch_no','exp_date'],
                    'gri_unique_key'
                );
            });

            // Tambah FK kondisional
            if (Schema::hasTable('goods_receipts')) {
                Schema::table('goods_receipt_items', function (Blueprint $t) {
                    try {
                        $t->foreign('goods_receipt_id', 'gri_goods_receipt_id_fk')
                          ->references('id')->on('goods_receipts')
                          ->cascadeOnDelete();
                    } catch (\Throwable $e) { /* abaikan */ }
                });
            }
            if (Schema::hasTable('purchase_items')) {
                Schema::table('goods_receipt_items', function (Blueprint $t) {
                    try {
                        $t->foreign('purchase_item_id', 'gri_purchase_item_id_fk')
                          ->references('id')->on('purchase_items')
                          ->restrictOnDelete();
                    } catch (\Throwable $e) { /* abaikan */ }
                });
            }
            if (Schema::hasTable('products')) {
                Schema::table('goods_receipt_items', function (Blueprint $t) {
                    try {
                        $t->foreign('product_id', 'gri_product_id_fk')
                          ->references('id')->on('products')
                          ->restrictOnDelete();
                    } catch (\Throwable $e) { /* abaikan */ }
                });
            }
        }
    }

    public function down(): void
    {
        // ===== drop detail dulu =====
        if (Schema::hasTable('goods_receipt_items')) {
            Schema::table('goods_receipt_items', function (Blueprint $t) {
                // putus FK (aman dengan try/catch)
                try { $t->dropForeign('gri_goods_receipt_id_fk'); } catch (\Throwable $e) {}
                try { $t->dropForeign('gri_purchase_item_id_fk'); } catch (\Throwable $e) {}
                try { $t->dropForeign('gri_product_id_fk'); } catch (\Throwable $e) {}
                try { $t->dropUnique('gri_unique_key'); } catch (\Throwable $e) {}
                try { $t->dropIndex('gri_product_id_index'); } catch (\Throwable $e) {}
                try { $t->dropIndex('gri_batch_no_index'); } catch (\Throwable $e) {}
            });
            Schema::dropIfExists('goods_receipt_items');
        }

        // ===== header =====
        if (Schema::hasTable('goods_receipts')) {
            Schema::table('goods_receipts', function (Blueprint $t) {
                try { $t->dropForeign('goods_receipts_purchase_id_fk'); } catch (\Throwable $e) {}
                try { $t->dropForeign('goods_receipts_supplier_id_fk'); } catch (\Throwable $e) {}
                try { $t->dropIndex('goods_receipts_received_at_index'); } catch (\Throwable $e) {}
                try { $t->dropIndex('goods_receipts_supplier_id_index'); } catch (\Throwable $e) {}
            });
            Schema::dropIfExists('goods_receipts');
        }

        // ===== rollback perubahan tambahan (opsional) =====
        if (Schema::hasTable('purchase_items') && Schema::hasColumn('purchase_items', 'qty_received')) {
            try {
                Schema::table('purchase_items', fn(Blueprint $t) => $t->dropColumn('qty_received'));
            } catch (\Throwable $e) { /* abaikan */ }
        }

        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'status')) {
            try {
                Schema::table('purchases', function (Blueprint $t) {
                    try { $t->dropIndex('purchases_status_index'); } catch (\Throwable $e) {}
                    $t->dropColumn('status');
                });
            } catch (\Throwable $e) { /* abaikan */ }
        }
    }
};
