<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nonaktifkan FK sementara (MySQL)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Hapus tabel detail dulu (punya FK ke header)
        if (Schema::hasTable('supplier_return_items')) {
            Schema::drop('supplier_return_items');
        }

        // Hapus tabel header
        if (Schema::hasTable('supplier_returns')) {
            Schema::drop('supplier_returns');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // OPTIONAL: buat ulang tabel minimal kalau di-rollback
        if (!Schema::hasTable('supplier_returns')) {
            Schema::create('supplier_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnUpdate()->restrictOnDelete();
                $table->enum('type', ['send_back','write_off']);
                $table->string('note')->nullable();
                $table->decimal('total_claim', 15, 2)->default(0);
                if (!Schema::hasColumn('supplier_returns', 'tanggal')) {
                    $table->timestamp('tanggal')->nullable();
                }
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('supplier_return_items')) {
            Schema::create('supplier_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_return_id')->constrained('supplier_returns')->cascadeOnDelete();
                $table->foreignId('goods_receipt_item_id')->constrained('goods_receipt_items')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
                $table->integer('qty');
                $table->decimal('claim_amount', 15, 2)->default(0);
                $table->string('reason')->nullable();
                $table->string('batch_no')->nullable();
                $table->date('exp_date')->nullable();
                $table->timestamps();
            });
        }
    }
};
