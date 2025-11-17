<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1) Buat table tanpa FK dulu agar tidak gagal kalau tabel induk belum ada
        Schema::create('purchase_items', function (Blueprint $table) {
            // pastikan InnoDB untuk FK (default Laravel biasanya InnoDB)
            if (property_exists($table, 'engine')) {
                $table->engine = 'InnoDB';
            }

            $table->id();

            // Kolom relasi (FK ditambahkan setelah create, kondisional)
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('product_id');

            // Detail item
            $table->integer('qty');                                // jumlah dipesan
            $table->string('uom', 20)->nullable();                 // satuan (opsional)
            $table->integer('qty_received')->default(0);           // tracking penerimaan

            // Harga & perhitungan
            $table->decimal('cost', 18, 2)->default(0);            // harga satuan
            $table->decimal('discount', 18, 2)->default(0);        // diskon nominal per-line
            $table->decimal('tax_pct', 5, 2)->default(0);          // pajak % per-line (opsional)
            $table->decimal('subtotal', 18, 2)->default(0);        // qty*cost - discount + tax

            $table->timestamps();

            // Index yang sering dipakai
            $table->index(['purchase_id', 'product_id'], 'pi_purchase_product_idx');
            $table->index('qty_received', 'pi_qty_received_idx');
        });

        // 2) Tambah FK secara kondisional agar tidak error kalau tabel induk belum ada
        //    (mis. urutan timestamp kurang pas saat migrate:fresh)
        if (Schema::hasTable('purchases')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                try {
                    $table->foreign('purchase_id', 'purchase_items_purchase_id_fk')
                          ->references('id')->on('purchases')
                          ->cascadeOnDelete();
                } catch (\Throwable $e) {
                    // abaikan jika constraint sudah ada / environment membatasi
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                try {
                    $table->foreign('product_id', 'purchase_items_product_id_fk')
                          ->references('id')->on('products')
                          ->restrictOnDelete();
                } catch (\Throwable $e) {
                    // abaikan jika constraint sudah ada / environment membatasi
                }
            });
        }
    }

    public function down(): void {
        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                // Putuskan FK bernama eksplisit sebelum drop table (safe try/catch)
                try { $table->dropForeign('purchase_items_purchase_id_fk'); } catch (\Throwable $e) {}
                try { $table->dropForeign('purchase_items_product_id_fk'); } catch (\Throwable $e) {}
                // Drop index custom (opsional, table akan di-drop juga)
                try { $table->dropIndex('pi_purchase_product_idx'); } catch (\Throwable $e) {}
                try { $table->dropIndex('pi_qty_received_idx'); } catch (\Throwable $e) {}
            });
        }

        Schema::dropIfExists('purchase_items');
    }
};
