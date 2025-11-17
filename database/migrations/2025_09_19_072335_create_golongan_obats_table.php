<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('drug_groups', function (Blueprint $t) {
      $t->id();
      $t->string('code', 32)->unique();           // contoh: OTC, RX, NARC, HERB
      $t->string('name');                          // nama lengkap golongan
      $t->string('description')->nullable();
      $t->boolean('is_active')->default(true);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('drug_groups'); }
};
