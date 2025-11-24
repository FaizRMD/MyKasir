<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('pembelian', 'status')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->string('status', 20)->default('draft')->after('notes')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembelian', 'status')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
