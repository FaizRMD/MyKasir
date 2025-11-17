<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Buat tabel tanpa foreign key dulu
        if (!Schema::hasTable('payables')) {
            Schema::create('payables', function (Blueprint $t) {
                if (property_exists($t, 'engine')) $t->engine = 'InnoDB';

                $t->id();

                // simpan kolom relasi sebagai unsignedBigInteger dulu
                $t->unsignedBigInteger('supplier_id');
                $t->unsignedBigInteger('purchase_id');

                $t->date('issue_date')->nullable();
                $t->date('due_date')->nullable();

                $t->decimal('amount', 16, 2)->default(0);
                $t->decimal('paid_amount', 16, 2)->default(0);
                $t->string('status', 16)->default('unpaid'); // unpaid | partial | paid
                $t->string('ref_no')->nullable();            // nomor invoice supplier (opsional)
                $t->text('note')->nullable();

                $t->timestamps();

                // index bantu
                $t->index('supplier_id', 'payables_supplier_id_index');
                $t->index('purchase_id', 'payables_purchase_id_index');
                $t->index('status', 'payables_status_index');
            });
        }

        // 2) Tambahkan FK secara kondisional agar tidak error jika tabel referensi belum ada
        if (Schema::hasTable('payables')) {
            if (Schema::hasTable('suppliers')) {
                Schema::table('payables', function (Blueprint $t) {
                    try {
                        $t->foreign('supplier_id', 'payables_supplier_id_fk')
                          ->references('id')->on('suppliers')
                          ->restrictOnDelete();
                    } catch (\Throwable $e) { /* abaikan jika sudah ada */ }
                });
            }

            if (Schema::hasTable('purchases')) {
                Schema::table('payables', function (Blueprint $t) {
                    try {
                        $t->foreign('purchase_id', 'payables_purchase_id_fk')
                          ->references('id')->on('purchases')
                          ->cascadeOnDelete();
                    } catch (\Throwable $e) { /* abaikan jika sudah ada */ }
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payables')) {
            Schema::table('payables', function (Blueprint $t) {
                // putuskan FK & index bila ada (aman dengan try/catch)
                try { $t->dropForeign('payables_supplier_id_fk'); } catch (\Throwable $e) {}
                try { $t->dropForeign('payables_purchase_id_fk'); } catch (\Throwable $e) {}

                try { $t->dropIndex('payables_supplier_id_index'); } catch (\Throwable $e) {}
                try { $t->dropIndex('payables_purchase_id_index'); } catch (\Throwable $e) {}
                try { $t->dropIndex('payables_status_index'); } catch (\Throwable $e) {}
            });
        }

        Schema::dropIfExists('payables');
    }
};
