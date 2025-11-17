<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('warehouses')) return;

        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->nullable()->unique();
            $t->string('name', 150);
            $t->string('address', 255)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('warehouses');
    }
};
