<?php

// database/migrations/2025_10_04_000001_add_user_id_to_sales_and_inventory.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SALES
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'user_id')) {
                $table->foreignId('user_id')
                      ->after('id')
                      ->nullable() // nullable dulu supaya aman saat migrasi data lama
                      ->constrained()
                      ->nullOnDelete();
            }
        });

        // INVENTORY (sesuaikan nama tabelmu, mis: inventory_movements / inventory_mutations)
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_movements', 'user_id')) {
                    $table->foreignId('user_id')
                          ->after('id')
                          ->nullable()
                          ->constrained()
                          ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'user_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasTable('inventory_movements') && Schema::hasColumn('inventory_movements', 'user_id')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
