<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('products')) return;

        Schema::table('products', function (Blueprint $t) {
            if (!Schema::hasColumn('products', 'last_buy_price')) {

                // Cari kolom yang benar-benar ada untuk penempatan
                $after = null;
                foreach (['price', 'purchase_price', 'buy_price', 'harga_beli'] as $cand) {
                    if (Schema::hasColumn('products', $cand)) {
                        $after = $cand;
                        break;
                    }
                }

                if ($after) {
                    $t->decimal('last_buy_price', 18, 2)->default(0)->after($after);
                } else {
                    // Jika tidak ada kolom acuan, tambahkan tanpa after (paling aman)
                    $t->decimal('last_buy_price', 18, 2)->default(0);
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) return;

        Schema::table('products', function (Blueprint $t) {
            if (Schema::hasColumn('products', 'last_buy_price')) {
                $t->dropColumn('last_buy_price');
            }
        });
    }
};
