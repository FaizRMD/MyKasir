<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            // tambahkan kolom kasir
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);

            // pembayaran
            $table->decimal('paid', 12, 2)->default(0);
            $table->decimal('change', 12, 2)->default(0);
            $table->string('payment_method', 32)->default('cash'); // cash/transfer/qris/dll

            $table->string('notes')->nullable();
            $table->timestamps();

            // index untuk optimasi query
            $table->index(['customer_id', 'created_at']);
            $table->index(['cashier_id', 'created_at']); // index tambahan biar cepat difilter
        });
    }

    public function down(): void {
        Schema::dropIfExists('sales');
    }
};
