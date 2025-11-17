<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('purchase_items', function (Blueprint $t) {
            if (!Schema::hasColumn('purchase_items','uom')) {
                $t->string('uom', 50)->nullable()->after('qty');
            }
            if (!Schema::hasColumn('purchase_items','price')) {
                $t->decimal('price', 18, 2)->default(0)->after('uom');
            }
            if (!Schema::hasColumn('purchase_items','disc_percent')) {
                $t->decimal('disc_percent', 8, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('purchase_items','disc_amount')) {
                $t->decimal('disc_amount', 18, 2)->default(0)->after('disc_percent');
            }
        });
    }

    public function down(): void {
        Schema::table('purchase_items', function (Blueprint $t) {
            foreach (['uom','price','disc_percent','disc_amount'] as $col) {
                if (Schema::hasColumn('purchase_items',$col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
