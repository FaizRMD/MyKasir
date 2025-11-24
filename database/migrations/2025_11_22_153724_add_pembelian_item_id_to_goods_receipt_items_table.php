<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'pembelian_item_id')) {
                $table->unsignedBigInteger('pembelian_item_id')
                    ->nullable()
                    ->after('goods_receipt_id');

                $table->foreign('pembelian_item_id')
                    ->references('id')
                    ->on('pembelian_items')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipt_items', 'pembelian_item_id')) {
                $table->dropForeign(['pembelian_item_id']);
                $table->dropColumn('pembelian_item_id');
            }
        });
    }
};
