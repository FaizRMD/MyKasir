<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('drug_locations', function (Blueprint $t) {
      $t->id();
      $t->string('code', 32)->unique();          // contoh: RAK-A1, KULKAS
      $t->string('name');                        // nama lokasi
      $t->string('description')->nullable();
      $t->unsignedInteger('sort_order')->default(0);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('drug_locations'); }
};
