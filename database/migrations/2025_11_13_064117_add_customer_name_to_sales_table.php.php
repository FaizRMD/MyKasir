<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (! Schema::hasColumn('sales', 'customer_name')) {
            Schema::table('sales', function (Blueprint $t) {
                $t->string('customer_name', 150)->nullable()->after('customer_id');
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('sales', 'customer_name')) {
            Schema::table('sales', function (Blueprint $t) {
                $t->dropColumn('customer_name');
            });
        }
    }
};
