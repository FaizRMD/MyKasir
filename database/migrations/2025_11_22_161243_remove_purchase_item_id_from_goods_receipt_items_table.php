<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan tabel ada
        if (!Schema::hasTable('goods_receipt_items')) {
            return;
        }

        if (Schema::hasColumn('goods_receipt_items', 'purchase_item_id')) {

            // MATIKAN FK CHECK JAGA-JAGA
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 1. HAPUS SEMUA FOREIGN KEY YANG MENYENTUH purchase_item_id
            try {
                DB::statement("ALTER TABLE `goods_receipt_items` DROP FOREIGN KEY `gri_purchase_item_id_fk`");
            } catch (\Throwable $e) {}

            try {
                DB::statement("ALTER TABLE `goods_receipt_items` DROP FOREIGN KEY `goods_receipt_items_purchase_item_id_fk`");
            } catch (\Throwable $e) {}

            try {
                DB::statement("ALTER TABLE `goods_receipt_items` DROP FOREIGN KEY `goods_receipt_items_purchase_item_id_foreign`");
            } catch (\Throwable $e) {}

            // 2. DROP COLUMN
            try {
                Schema::table('goods_receipt_items', function (Blueprint $table) {
                    $table->dropColumn('purchase_item_id');
                });
            } catch (\Throwable $e) {
                // fallback jika Laravel gagal drop column langsung
                try {
                    DB::statement("ALTER TABLE `goods_receipt_items` DROP COLUMN `purchase_item_id`");
                } catch (\Throwable $e2) {
                    // biarkan jika sudah terhapus sebelumnya
                }
            }

            // AKTIFKAN KEMBALI FK CHECK
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('goods_receipt_items')) {
            return;
        }

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'purchase_item_id')) {
                $table->unsignedBigInteger('purchase_item_id')->nullable()->after('goods_receipt_id');
            }
        });
    }
};
