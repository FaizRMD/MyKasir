<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan tabel products ada
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            // Tambah kolom code jika belum ada
            if (!Schema::hasColumn('products', 'code')) {
                $table->string('code', 100)->nullable()->after('name');
            }

            // Tambah kolom sku jika belum ada
            if (!Schema::hasColumn('products', 'sku')) {
                // kalau code sudah ada, taruh setelah code,
                // kalau tidak, taruh setelah name
                if (Schema::hasColumn('products', 'code')) {
                    $table->string('sku', 100)->nullable()->after('code');
                } else {
                    $table->string('sku', 100)->nullable()->after('name');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sku')) {
                $table->dropColumn('sku');
            }
            if (Schema::hasColumn('products', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
