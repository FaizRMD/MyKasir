<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('apotekers', function (Blueprint $t) {
      $t->id();
      $t->string('nip', 32)->nullable()->unique();     // nomor induk pegawai (opsional)
      $t->string('name');
      $t->string('sip', 64)->nullable();               // Surat Izin Praktik
      $t->date('sip_valid_until')->nullable();
      $t->string('phone', 64)->nullable();
      $t->string('email', 128)->nullable();
      $t->string('address')->nullable();
      $t->boolean('is_active')->default(true);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('apotekers'); }
};
