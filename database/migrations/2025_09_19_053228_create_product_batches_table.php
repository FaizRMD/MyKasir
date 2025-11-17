<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('batch_no', 64)->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('buy_price', 12, 2)->default(0);
            $table->string('location', 64)->nullable(); // rak/lemari

            $table->timestamps();

            $table->index(['product_id','expiry_date']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('product_batches');
    }
};
