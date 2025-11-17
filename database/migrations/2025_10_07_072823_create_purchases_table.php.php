<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // CREATE purchases (tanpa FK dulu supaya aman urutan)
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $t) {
                if (property_exists($t, 'engine')) $t->engine = 'InnoDB';

                $t->id();

                // nomor PO (unik, boleh null saat awal lalu diisi saat store)
                $t->string('po_no', 30)->nullable()->unique();

                // kolom relasi (FK ditambahkan setelah create)
                $t->unsignedBigInteger('supplier_id');
                $t->unsignedBigInteger('user_id')->nullable();

                // tanggal PO
                $t->date('po_date');

                // atribut PO (selaras dengan controller & view)
                $t->string('type', 20)->default('NON KONSINYASI'); // NON KONSINYASI|KONSINYASI
                $t->string('category', 40)->default('Reguler');
                $t->string('print_type', 30)->default('INV_A5');

                // status UPPERCASE
                $t->string('status', 32)->default('DRAFT'); // DRAFT|ORDERED|PARTIAL_RECEIVED|RECEIVED
                $t->decimal('total', 18, 2)->default(0);
                $t->string('note', 500)->nullable();

                $t->timestamps();

                // index bantu
                $t->index('po_date', 'purchases_po_date_index');
                $t->index('status', 'purchases_status_index');
                $t->index('supplier_id', 'purchases_supplier_id_index');
            });
        }

        // Tambahkan FK kondisional (hindari error jika tabel referensi belum ada)
        if (Schema::hasTable('purchases')) {
            if (Schema::hasTable('suppliers')) {
                Schema::table('purchases', function (Blueprint $t) {
                    try {
                        $t->foreign('supplier_id', 'purchases_supplier_id_fk')
                          ->references('id')->on('suppliers')
                          ->restrictOnDelete();
                    } catch (\Throwable $e) { /* abaikan jika sudah ada */ }
                });
            }
            if (Schema::hasTable('users')) {
                Schema::table('purchases', function (Blueprint $t) {
                    try {
                        $t->foreign('user_id', 'purchases_user_id_fk')
                          ->references('id')->on('users')
                          ->nullOnDelete(); // jika user dihapus, set NULL
                    } catch (\Throwable $e) { /* abaikan jika sudah ada */ }
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $t) {
                // lepas FK & index (aman dengan try/catch)
                try { $t->dropForeign('purchases_supplier_id_fk'); } catch (\Throwable $e) {}
                try { $t->dropForeign('purchases_user_id_fk'); } catch (\Throwable $e) {}

                try { $t->dropIndex('purchases_po_date_index'); } catch (\Throwable $e) {}
                try { $t->dropIndex('purchases_status_index'); } catch (\Throwable $e) {}
                try { $t->dropIndex('purchases_supplier_id_index'); } catch (\Throwable $e) {}
            });
        }

        Schema::dropIfExists('purchases');
    }
};
