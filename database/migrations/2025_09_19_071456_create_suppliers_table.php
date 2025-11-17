<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->nullable(); // bisa kosong
            $table->string('name', 255);
            $table->string('contact_person', 128)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email', 128)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 64)->nullable();
            $table->string('npwp', 64)->nullable();
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
