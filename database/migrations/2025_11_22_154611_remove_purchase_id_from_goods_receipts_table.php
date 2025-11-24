<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('goods_receipts')) {
            return;
        }

        // Hanya kalau kolom purchase_id masih ada
        if (Schema::hasColumn('goods_receipts', 'purchase_id')) {

            // 1. Coba hapus foreign key dengan berbagai kemungkinan nama
            try {
                DB::statement('ALTER TABLE `goods_receipts` DROP FOREIGN KEY `goods_receipts_purchase_id_fk`');
            } catch (\Throwable $e) {
                // abaikan kalau tidak ada
            }

            try {
                DB::statement('ALTER TABLE `goods_receipts` DROP FOREIGN KEY `goods_receipts_purchase_id_foreign`');
            } catch (\Throwable $e) {
                // abaikan kalau tidak ada
            }

            // 2. Terakhir, kalau masih ada FK yang nempel ke kolom purchase_id dengan nama lain,
            //    matikan FK check sebentar (aman karena kita cuma buang FK & kolom lama)
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            } catch (\Throwable $e) {
                // kalau MySQL versi lama error, biarkan saja
            }

            Schema::table('goods_receipts', function (Blueprint $table) {
                if (Schema::hasColumn('goods_receipts', 'purchase_id')) {
                    $table->dropColumn('purchase_id');
                }
            });

            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $e) {
                // abaikan
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('goods_receipts')) {
            return;
        }

        Schema::table('goods_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipts', 'purchase_id')) {
                $table->unsignedBigInteger('purchase_id')->nullable()->after('id');
                // tidak perlu FK lagi, cukup kolom biasa
            }
        });
    }
};
