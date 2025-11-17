<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Sesuaikan panjang tipe kolommu (VARCHAR(32) asumsi)
        DB::statement("ALTER TABLE products MODIFY drug_class VARCHAR(32) NOT NULL DEFAULT 'Other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY drug_class VARCHAR(32) NOT NULL");
    }
};
