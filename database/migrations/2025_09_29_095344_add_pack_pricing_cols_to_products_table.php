<?php

// database/migrations/2025_09_29_000001_add_pack_pricing_to_products_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            // Jika tabel belum ada, jangan eksekusi apa-apa di migration ini
            return;
        }

        Schema::table('products', function (Blueprint $t) {
            if (!Schema::hasColumn('products','pack_name'))     $t->string('pack_name',50)->default('box')->after('tax_percent');
            if (!Schema::hasColumn('products','pack_qty'))      $t->unsignedInteger('pack_qty')->default(1)->after('pack_name');
            if (!Schema::hasColumn('products','sell_unit'))     $t->string('sell_unit',50)->default('unit')->after('pack_qty');
            if (!Schema::hasColumn('products','buy_price_pack'))$t->decimal('buy_price_pack',14,2)->default(0)->after('sell_unit');
            if (!Schema::hasColumn('products','ppn_percent'))   $t->decimal('ppn_percent',5,2)->default(11.00)->after('buy_price_pack');
            if (!Schema::hasColumn('products','disc_percent'))  $t->decimal('disc_percent',5,2)->default(0.00)->after('ppn_percent');
            if (!Schema::hasColumn('products','disc_amount'))   $t->decimal('disc_amount',14,2)->default(0.00)->after('disc_percent');
            if (!Schema::hasColumn('products','margin_amount')) $t->decimal('margin_amount',14,2)->default(0.00)->after('disc_amount');
            if (!Schema::hasColumn('products','margin_percent'))$t->decimal('margin_percent',6,2)->default(0.00)->after('margin_amount');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) return;

        Schema::table('products', function (Blueprint $t) {
            foreach ([
                'pack_name','pack_qty','sell_unit',
                'buy_price_pack','ppn_percent','disc_percent','disc_amount',
                'margin_amount','margin_percent',
            ] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
