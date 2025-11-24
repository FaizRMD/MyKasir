<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            // kalau sudah ada kolomnya, jangan buat lagi
            if (!Schema::hasColumn('goods_receipts', 'pembelian_id')) {
                $table->unsignedBigInteger('pembelian_id')
                    ->nullable()
                    ->after('id');

                // kalau nama tabel pembelian kamu 'pembelian'
                $table->foreign('pembelian_id')
                    ->references('id')
                    ->on('pembelian')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'pembelian_id')) {
                $table->dropForeign(['pembelian_id']);
                $table->dropColumn('pembelian_id');
            }
        });
    }
};
