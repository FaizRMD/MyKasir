<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('goods_receipts', 'status')) {
            Schema::table('goods_receipts', function (Blueprint $table) {
                $table->string('status')->default('draft')->after('grn_no'); // draft, received
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('goods_receipts', 'status')) {
            Schema::table('goods_receipts', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
