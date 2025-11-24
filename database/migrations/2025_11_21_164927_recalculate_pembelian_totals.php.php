<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Tambah kolom status jika belum ada
        if (Schema::hasTable('pembelian') && !Schema::hasColumn('pembelian', 'status')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->string('status', 32)->default('draft')->after('notes');
                $table->index('status');
            });
            echo "✅ Kolom 'status' ditambahkan ke tabel pembelian\n";
        }

        // 2. Pastikan foreign key supplier_id ada
        if (Schema::hasTable('pembelian') && Schema::hasTable('suppliers')) {
            try {
                $foreignKeys = $this->getTableForeignKeys('pembelian');
                if (!in_array('pembelian_supplier_id_foreign', $foreignKeys)) {
                    Schema::table('pembelian', function (Blueprint $table) {
                        $table->foreign('supplier_id')
                            ->references('id')->on('suppliers')
                            ->restrictOnDelete();
                    });
                    echo "✅ Foreign key supplier_id ditambahkan\n";
                }
            } catch (\Exception $e) {
                echo "⚠️ Skip foreign key supplier_id: " . $e->getMessage() . "\n";
            }
        }

        // 3. Pastikan foreign key warehouse_id ada
        if (Schema::hasTable('pembelian') && Schema::hasTable('warehouses')) {
            try {
                $foreignKeys = $this->getTableForeignKeys('pembelian');
                if (!in_array('pembelian_warehouse_id_foreign', $foreignKeys)) {
                    Schema::table('pembelian', function (Blueprint $table) {
                        $table->foreign('warehouse_id')
                            ->references('id')->on('warehouses')
                            ->nullOnDelete();
                    });
                    echo "✅ Foreign key warehouse_id ditambahkan\n";
                }
            } catch (\Exception $e) {
                echo "⚠️ Skip foreign key warehouse_id: " . $e->getMessage() . "\n";
            }
        }

        // 4. PERBAIKI DATA: Hitung ulang SEMUA total (termasuk yang sudah ada nilai)
        $this->recalculatePembelianTotals();

        // 5. Update status default
        if (Schema::hasColumn('pembelian', 'status')) {
            DB::table('pembelian')
                ->whereNull('status')
                ->orWhere('status', '')
                ->update(['status' => 'draft']);
            echo "✅ Status default 'draft' diset untuk data lama\n";
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pembelian')) {
            Schema::table('pembelian', function (Blueprint $table) {
                try { $table->dropForeign(['supplier_id']); } catch (\Exception $e) {}
                try { $table->dropForeign(['warehouse_id']); } catch (\Exception $e) {}
            });

            if (Schema::hasColumn('pembelian', 'status')) {
                Schema::table('pembelian', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }
        }
    }

    private function recalculatePembelianTotals(): void
    {
        if (!Schema::hasTable('pembelian') || !Schema::hasTable('pembelian_items')) {
            echo "⚠️ Tabel pembelian atau pembelian_items tidak ditemukan\n";
            return;
        }

        // Ambil SEMUA pembelian yang memiliki items
        $pembelians = DB::table('pembelian as p')
            ->join('pembelian_items as pi', 'p.id', '=', 'pi.pembelian_id')
            ->select('p.id')
            ->groupBy('p.id')
            ->pluck('id');

        if ($pembelians->isEmpty()) {
            echo "⚠️ Tidak ada pembelian dengan items\n";
            return;
        }

        echo "🔄 Menghitung ulang " . $pembelians->count() . " pembelian...\n";

        foreach ($pembelians as $pembelianId) {
            $pembelian = DB::table('pembelian')->where('id', $pembelianId)->first();

            if (!$pembelian) continue;

            // Ambil items
            $items = DB::table('pembelian_items')
                ->where('pembelian_id', $pembelianId)
                ->get();

            if ($items->isEmpty()) {
                echo "⚠️ Pembelian ID {$pembelianId} tidak memiliki items\n";
                continue;
            }

            $grossTotal = 0;
            $discountTotal = 0;

            // Hitung per item
            foreach ($items as $item) {
                $qty = (float) ($item->qty ?? 0);
                $buyPrice = (float) ($item->buy_price ?? 0);
                $discPercent = (float) ($item->disc_percent ?? 0);
                $discAmount = (float) ($item->disc_amount ?? 0);

                // Subtotal sebelum diskon
                $subtotalBeforeDisc = $qty * $buyPrice;

                // Diskon persen
                $discPercentNominal = ($discPercent / 100) * $subtotalBeforeDisc;

                // Total diskon item
                $totalItemDiscount = $discPercentNominal + $discAmount;

                // Subtotal setelah diskon
                $subtotalAfterDisc = max(0, $subtotalBeforeDisc - $totalItemDiscount);

                // Akumulasi
                $grossTotal += $subtotalAfterDisc;
                $discountTotal += $totalItemDiscount;

                // Update item subtotal, hpp, hna_ppn
                $hpp = $qty > 0 ? $subtotalAfterDisc / $qty : 0;
                $taxPercent = (float) ($pembelian->tax_percent ?? 0);
                $hnaPpn = $hpp * (1 + ($taxPercent / 100));

                DB::table('pembelian_items')
                    ->where('id', $item->id)
                    ->update([
                        'subtotal' => round($subtotalAfterDisc, 2),
                        'disc_nominal' => round($totalItemDiscount, 2),
                        'hpp' => round($hpp, 4),
                        'hna_ppn' => round($hnaPpn, 4),
                        'updated_at' => now(),
                    ]);
            }

            // Hitung tax dan extra cost
            $taxPercent = (float) ($pembelian->tax_percent ?? 0);
            $extraCost = (float) ($pembelian->extra_cost ?? 0);

            $taxAmount = $grossTotal * ($taxPercent / 100);
            $netTotal = $grossTotal + $taxAmount + $extraCost;

            // Update pembelian header
            DB::table('pembelian')
                ->where('id', $pembelianId)
                ->update([
                    'gross' => round($grossTotal, 2),
                    'discount_total' => round($discountTotal, 2),
                    'tax_amount' => round($taxAmount, 2),
                    'net_total' => round($netTotal, 2),
                    'updated_at' => now(),
                ]);

            echo "✅ Pembelian ID {$pembelianId} - Gross: Rp " . number_format($grossTotal, 0, ',', '.')
                . " | Net: Rp " . number_format($netTotal, 0, ',', '.') . "\n";
        }

        echo "✅ Selesai menghitung ulang pembelian!\n";
    }

    private function getTableForeignKeys(string $table): array
    {
        $foreignKeys = [];
        try {
            $results = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [config('database.connections.mysql.database'), $table]);

            foreach ($results as $result) {
                $foreignKeys[] = $result->CONSTRAINT_NAME;
            }
        } catch (\Exception $e) {
            $foreignKeys = [];
        }
        return $foreignKeys;
    }
};
