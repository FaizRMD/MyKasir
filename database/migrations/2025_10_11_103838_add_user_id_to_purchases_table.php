<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Jika tabel purchases belum ada (urutan migrasi), jangan error
        if (!Schema::hasTable('purchases')) {
            return; // no-op, biar migrate tetap lanjut
        }

        // 1) Tambah kolom user_id (nullable) jika belum ada
        if (!Schema::hasColumn('purchases', 'user_id')) {
            Schema::table('purchases', function (Blueprint $t) {
                // Tambahkan kolom dulu TANPA constrained() supaya tidak gagal jika 'users' belum ada
                $t->unsignedBigInteger('user_id')->nullable()->after('supplier_id');
            });
        }

        // 2) Backfill dari created_by -> user_id (opsional) jika kolom created_by ada
        if (Schema::hasColumn('purchases', 'created_by') && Schema::hasColumn('purchases', 'user_id')) {
            DB::table('purchases')
                ->whereNull('user_id')
                ->whereNotNull('created_by')
                ->update(['user_id' => DB::raw('created_by')]);
        }

        // 3) Tambah INDEX untuk user_id (aman kalau sudah ada)
        if (Schema::hasColumn('purchases', 'user_id')) {
            try {
                Schema::table('purchases', function (Blueprint $t) {
                    $t->index('user_id', 'purchases_user_id_index');
                });
            } catch (\Throwable $e) {
                // kemungkinan index sudah ada — aman diabaikan
            }
        }

        // 4) Tambah FOREIGN KEY secara kondisional (hanya jika tabel users tersedia)
        if (Schema::hasColumn('purchases', 'user_id') && Schema::hasTable('users')) {
            try {
                Schema::table('purchases', function (Blueprint $t) {
                    // Nama constraint eksplisit agar mudah di-drop saat rollback
                    $t->foreign('user_id', 'purchases_user_id_foreign')
                      ->references('id')->on('users')
                      ->nullOnDelete(); // kalau user dihapus, set NULL
                });
            } catch (\Throwable $e) {
                // abaikan jika constraint sudah terpasang atau engine membatasi
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('purchases') || !Schema::hasColumn('purchases', 'user_id')) {
            return;
        }

        Schema::table('purchases', function (Blueprint $t) {
            // Putuskan FK & index jika ada, tapi jangan bikin error kalau tidak ada
            try { $t->dropForeign('purchases_user_id_foreign'); } catch (\Throwable $e) {}
            try { $t->dropIndex('purchases_user_id_index'); } catch (\Throwable $e) {}
            try { $t->dropColumn('user_id'); } catch (\Throwable $e) {}
        });
    }
};
