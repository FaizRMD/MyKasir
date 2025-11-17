<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            // salin nama produk saat transaksi untuk audit historis
            $table->string('name');

            $table->unsignedInteger('qty');
            $table->decimal('price', 12, 2);       // harga satuan saat transaksi
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('total', 12, 2);       // line total (sudah termasuk pajak jika dihitung)

            // opsional: catat batch_no yg terpakai (jika ingin)
            $table->string('batch_no', 64)->nullable();

            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('sale_items');
    }
};
