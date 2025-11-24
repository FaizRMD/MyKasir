<?php

namespace App\Console\Commands;

use App\Models\Pembelian;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPembelianTotals extends Command
{
    protected $signature = 'pembelian:fix-totals {--id=}';
    protected $description = 'Fix pembelian totals that are zero or incorrect';

    public function handle()
    {
        $this->info('🔧 Starting to fix pembelian totals...');

        $query = Pembelian::with('items');

        // Jika ada ID spesifik
        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        } else {
            // Fix semua yang totalnya 0
            $query->where('net_total', 0);
        }

        $pembelians = $query->get();

        if ($pembelians->isEmpty()) {
            $this->warn('⚠️  No pembelian found to fix.');
            return 0;
        }

        $this->info("Found {$pembelians->count()} pembelian(s) to fix...");

        $bar = $this->output->createProgressBar($pembelians->count());
        $bar->start();

        $fixed = 0;
        $failed = 0;

        foreach ($pembelians as $pembelian) {
            try {
                DB::beginTransaction();

                $totalGross = 0;
                $totalDiscount = 0;

                foreach ($pembelian->items as $item) {
                    // Gross per item
                    $itemGross = $item->qty * $item->buy_price;

                    // Total diskon item
                    $itemDiscount = ($item->disc_amount ?? 0) + ($item->disc_nominal ?? 0);

                    $totalGross += $itemGross;
                    $totalDiscount += $itemDiscount;
                }

                // Hitung gross setelah diskon
                $gross = $totalGross - $totalDiscount;

                // Hitung pajak
                $taxAmount = ($gross * $pembelian->tax_percent) / 100;

                // Net total
                $netTotal = $gross + $taxAmount + $pembelian->extra_cost;

                // Update pembelian
                $pembelian->update([
                    'gross' => $gross,
                    'discount_total' => $totalDiscount,
                    'tax_amount' => $taxAmount,
                    'net_total' => $netTotal,
                ]);

                // Update HNA+PPN untuk items
                if ($pembelian->tax_percent > 0) {
                    foreach ($pembelian->items as $item) {
                        $item->update([
                            'hna_ppn' => $item->hpp * (1 + ($pembelian->tax_percent / 100))
                        ]);
                    }
                }

                DB::commit();

                Log::info("Fixed pembelian #{$pembelian->id}", [
                    'po_no' => $pembelian->po_no,
                    'net_total' => $netTotal,
                ]);

                $fixed++;

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error("Failed to fix pembelian #{$pembelian->id}", [
                    'error' => $e->getMessage()
                ]);

                $this->error("\n❌ Failed to fix PO: {$pembelian->po_no} - {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Fixed: {$fixed}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }

        return 0;
    }
}
