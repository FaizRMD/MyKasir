<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            // barang masuk & keluar selalu terkait produk
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            // opsional: siapa pengguna/kasir yang melakukan input movement
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();

            // jika OUT, bisa dihubungkan ke penjualan
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['IN', 'OUT']);
            $table->integer('qty');

            $table->string('reference', 128)->nullable(); // nomor dokumen / invoice
            $table->string('notes')->nullable();

            // opsional: jejak batch yang dipakai/diterima
            $table->string('batch_no', 64)->nullable();
            $table->date('expiry_date')->nullable();

            $table->timestamps();

            $table->index(['product_id','type','created_at']);
            $table->index(['cashier_id','created_at']); // bantu filter per kasir
        });
    }

    public function down(): void {
        Schema::dropIfExists('inventory_movements');
    }
};
