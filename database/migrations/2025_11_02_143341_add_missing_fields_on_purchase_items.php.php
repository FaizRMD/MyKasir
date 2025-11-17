<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('purchase_items')) return;

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

        // Index (abaikan jika sudah ada)
        try {
            Schema::table('purchase_items', function (Blueprint $t) {
                $t->index('purchase_id', 'purchase_items_purchase_id_index');
                $t->index('product_id',  'purchase_items_product_id_index');
            });
        } catch (\Throwable $e) {
            // sudah ada → aman
        }
    }

    public function down(): void
    {
        // Non-destruktif: cukup drop index bila perlu
        try {
            Schema::table('purchase_items', function (Blueprint $t) {
                $t->dropIndex('purchase_items_purchase_id_index');
                $t->dropIndex('purchase_items_product_id_index');
            });
        } catch (\Throwable $e) {}
    }
};
