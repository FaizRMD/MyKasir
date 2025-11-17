<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tambah kolom hanya jika belum ada
        if (! Schema::hasColumn('sales', 'customer_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('customer_id')
                      ->nullable()
                      ->constrained('customers')
                      ->nullOnDelete()
                      ->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'customer_id')) {
            Schema::table('sales', function (Blueprint $table) {
                // Coba drop FK + kolom (nama constraint otomatis)
                try {
                    $table->dropConstrainedForeignId('customer_id');
                } catch (\Throwable $e) {
                    // Kalau FK tidak ada, drop kolom saja
                    $table->dropColumn('customer_id');
                }
            });
        }
    }
};
