<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pembelian_items')) return;

        Schema::create('pembelian_items', function (Blueprint $t) {
            $t->id();

            $t->foreignId('pembelian_id')
              ->constrained('pembelian')
              ->cascadeOnUpdate()
              ->cascadeOnDelete();

            $t->unsignedBigInteger('product_id')->index();

            $t->decimal('qty',18,4);
            $t->string('uom',50);
            $t->decimal('buy_price',18,2)->default(0);

            $t->decimal('disc_percent',8,2)->default(0);
            $t->decimal('disc_amount',18,2)->default(0);

            $t->decimal('subtotal',18,2)->default(0);
            $t->decimal('disc_nominal',18,2)->default(0);
            $t->decimal('hpp',18,4)->default(0);
            $t->decimal('hna_ppn',18,4)->default(0);

            $t->string('batch_no',100)->nullable()->index();
            $t->date('exp_date')->nullable()->index();

            $t->decimal('bonus_qty',18,4)->default(0);
            $t->string('bonus_uom',50)->nullable();
            $t->string('bonus_batch_no',100)->nullable();
            $t->date('bonus_exp_date')->nullable();

            $t->timestamps();

            $t->index(['product_id','batch_no']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('pembelian_items');
    }
};
