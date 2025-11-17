<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $t) {
            $t->id();

            // master references
            $t->unsignedBigInteger('supplier_id')->nullable()->index();
            $t->unsignedBigInteger('drug_group_id')->nullable()->index();
            $t->unsignedBigInteger('drug_location_id')->nullable()->index();

            // identitas
            $t->string('sku', 64)->nullable()->index();
            $t->string('name');
            $t->string('category', 128)->nullable();
            $t->string('unit', 32)->nullable();
            $t->string('barcode', 128)->nullable()->unique();

            // harga & pajak (unit) — kompat lama
            $t->decimal('buy_price', 12, 2)->default(0);
            $t->decimal('sell_price', 12, 2)->default(0);
            $t->decimal('tax_percent', 5, 2)->default(0);

            // ===== pack-pricing (baru) =====
            $t->string('pack_name', 50)->default('box');
            $t->unsignedInteger('pack_qty')->default(1);
            $t->string('sell_unit', 50)->default('unit');

            $t->decimal('buy_price_pack', 14, 2)->default(0);
            $t->decimal('ppn_percent', 5, 2)->default(11.00);
            $t->decimal('disc_percent', 5, 2)->default(0.00);
            $t->decimal('disc_amount', 14, 2)->default(0.00);

            $t->decimal('margin_amount', 14, 2)->default(0.00);
            $t->decimal('margin_percent', 6, 2)->default(0.00);

            // stok
            $t->unsignedInteger('stock')->default(0);
            $t->unsignedInteger('min_stock')->default(0);

            // atribut obat
            $t->boolean('is_medicine')->default(true);
            $t->enum('drug_class', ['OTC','Prescription','Narcotic','Herbal','Other'])->default('OTC');
            $t->boolean('is_compounded')->default(false);

            // status
            $t->boolean('is_active')->default(true);

            $t->timestamps();

            $t->index(['category','is_active']);
            $t->index(['is_medicine','drug_class']);
        });

        // FK (jalankan setelah tabel patokan ada)
        if (Schema::hasTable('suppliers')) {
            Schema::table('products', function (Blueprint $t) {
                $t->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            });
        }
        if (Schema::hasTable('drug_groups')) {
            Schema::table('products', function (Blueprint $t) {
                $t->foreign('drug_group_id')->references('id')->on('drug_groups')->nullOnDelete();
            });
        }
        if (Schema::hasTable('drug_locations')) {
            Schema::table('products', function (Blueprint $t) {
                $t->foreign('drug_location_id')->references('id')->on('drug_locations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) return;

        Schema::table('products', function (Blueprint $t) {
            try { $t->dropForeign(['supplier_id']); } catch (\Throwable $e) {}
            try { $t->dropForeign(['drug_group_id']); } catch (\Throwable $e) {}
            try { $t->dropForeign(['drug_location_id']); } catch (\Throwable $e) {}
        });

        Schema::dropIfExists('products');
    }
};
