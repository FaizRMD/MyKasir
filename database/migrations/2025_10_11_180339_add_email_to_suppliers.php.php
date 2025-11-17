<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('suppliers', function(Blueprint $t){
      if (!Schema::hasColumn('suppliers','email')) {
        $t->string('email')->nullable()->after('name')->index();
      }
    });
  }
  public function down(): void {
    Schema::table('suppliers', function(Blueprint $t){
      if (Schema::hasColumn('suppliers','email')) {
        $t->dropColumn('email');
      }
    });
  }
};
