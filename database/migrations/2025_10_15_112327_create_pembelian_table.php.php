<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pembelian')) return;

        Schema::create('pembelian', function (Blueprint $t) {
            $t->id();

            $t->string('po_no',50)->nullable()->index();
            $t->string('invoice_no',100)->nullable()->index();
            $t->date('invoice_date');

            // kolom biasa dulu, FK bisa ditambah belakangan
            $t->unsignedBigInteger('supplier_id')->index();
            $t->unsignedBigInteger('warehouse_id')->index();

            $t->string('payment_type',20)->index(); // TUNAI/HUTANG/KONSINYASI
            $t->string('cashbook',20)->nullable()->index(); // KAS_UMUM/BANK
            $t->date('due_date')->nullable()->index();

            $t->decimal('gross',18,2)->default(0);
            $t->decimal('discount_total',18,2)->default(0);
            $t->decimal('tax_percent',8,2)->default(0);
            $t->decimal('tax_amount',18,2)->default(0);
            $t->decimal('extra_cost',18,2)->default(0);
            $t->decimal('net_total',18,2)->default(0);
            $t->text('notes')->nullable();

            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pembelian');
    }
};
