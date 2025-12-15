<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('pembelian_items', 'qty_received')) {
            Schema::table('pembelian_items', function (Blueprint $table) {
                $table->decimal('qty_received', 18, 4)->default(0)->after('qty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembelian_items', 'qty_received')) {
            Schema::table('pembelian_items', function (Blueprint $table) {
                $table->dropColumn('qty_received');
            });
        }
    }
};
