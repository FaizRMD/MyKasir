<?php

namespace App\Console\Commands;

use App\Models\Pembelian;
use App\Models\PembelianItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugPembelian extends Command
{
    protected $signature = 'pembelian:debug {id?}';
    protected $description = 'Debug pembelian data structure and recalculate';

    public function handle()
    {
        $id = $this->argument('id');

        if ($id) {
            $this->debugSingle($id);
        } else {
            $this->debugAll();
        }

        return 0;
    }

    private function debugSingle($id)
    {
        $pembelian = Pembelian::find($id);

        if (!$pembelian) {
            $this->error("Pembelian ID {$id} tidak ditemukan!");
            return;
        }

        $this->info("=== DEBUG PEMBELIAN ID: {$id} ===\n");

        // Check header data
        $this->info("📋 HEADER DATA:");
        $this->line("PO No: " . ($pembelian->po_no ?? 'NULL'));
        $this->line("Invoice No: " . ($pembelian->invoice_no ?? 'NULL'));
        $this->line("Date: " . ($pembelian->invoice_date ?? 'NULL'));
        $this->line("Supplier ID: " . ($pembelian->supplier_id ?? 'NULL'));
        $this->line("Payment Type: " . ($pembelian->payment_type ?? 'NULL'));

        $this->newLine();
        $this->info("💰 TOTALS (Current):");
        $this->line("Gross: Rp " . number_format($pembelian->gross ?? 0, 0, ',', '.'));
        $this->line("Discount: Rp " . number_format($pembelian->discount_total ?? 0, 0, ',', '.'));
        $this->line("Tax %: " . ($pembelian->tax_percent ?? 0) . "%");
        $this->line("Tax Amount: Rp " . number_format($pembelian->tax_amount ?? 0, 0, ',', '.'));
        $this->line("Extra Cost: Rp " . number_format($pembelian->extra_cost ?? 0, 0, ',', '.'));
        $this->line("NET TOTAL: Rp " . number_format($pembelian->net_total ?? 0, 0, ',', '.'));

        // Check items
        $this->newLine();
        $items = PembelianItem::where('pembelian_id', $id)->get();
        $this->info("📦 ITEMS COUNT: " . $items->count());

        if ($items->isEmpty()) {
            $this->error("⚠️ TIDAK ADA ITEMS! Ini sebabnya total Rp 0");
            $this->warn("Kemungkinan masalah:");
            $this->warn("1. Items tidak tersimpan saat create pembelian");
            $this->warn("2. Transaction rollback");
            $this->warn("3. Error saat save items");
            return;
        }

        $this->newLine();
        $this->info("📊 ITEMS DETAIL:");

        $totalGross = 0;
        $totalDiscount = 0;

        foreach ($items as $index => $item) {
            $this->line("\nItem #" . ($index + 1) . ":");
            $this->line("  Product ID: " . $item->product_id);
            $this->line("  Qty: " . $item->qty);
            $this->line("  UOM: " . ($item->uom ?? 'NULL'));
            $this->line("  Buy Price: Rp " . number_format($item->buy_price ?? 0, 0, ',', '.'));
            $this->line("  Disc %: " . ($item->disc_percent ?? 0) . "%");
            $this->line("  Disc Amount: Rp " . number_format($item->disc_amount ?? 0, 0, ',', '.'));
            $this->line("  Disc Nominal: Rp " . number_format($item->disc_nominal ?? 0, 0, ',', '.'));
            $this->line("  Subtotal: Rp " . number_format($item->subtotal ?? 0, 0, ',', '.'));

            $itemGross = $item->qty * $item->buy_price;
            $itemDisc = ($item->disc_amount ?? 0) + ($item->disc_nominal ?? 0);

            $totalGross += $itemGross;
            $totalDiscount += $itemDisc;
        }

        // Calculate correct totals
        $this->newLine();
        $this->info("🧮 RECALCULATED TOTALS:");

        $grossCalculated = $totalGross - $totalDiscount;
        $taxAmount = ($grossCalculated * ($pembelian->tax_percent ?? 0)) / 100;
        $netTotal = $grossCalculated + $taxAmount + ($pembelian->extra_cost ?? 0);

        $this->line("Gross (after discount): Rp " . number_format($grossCalculated, 0, ',', '.'));
        $this->line("Tax Amount: Rp " . number_format($taxAmount, 0, ',', '.'));
        $this->line("Extra Cost: Rp " . number_format($pembelian->extra_cost ?? 0, 0, ',', '.'));
        $this->line("NET TOTAL: Rp " . number_format($netTotal, 0, ',', '.'));

        // Ask to update
        $this->newLine();
        if ($this->confirm('Update dengan total yang benar?', true)) {
            $pembelian->update([
                'gross' => $grossCalculated,
                'discount_total' => $totalDiscount,
                'tax_amount' => $taxAmount,
                'net_total' => $netTotal,
            ]);

            $this->info("✅ Total berhasil diupdate!");
            $this->info("Net Total Baru: Rp " . number_format($netTotal, 0, ',', '.'));
        }
    }

    private function debugAll()
    {
        $this->info("=== CHECKING ALL PEMBELIAN WITH RP 0 ===\n");

        $pembelians = Pembelian::where(function($q) {
            $q->whereNull('net_total')
              ->orWhere('net_total', 0);
        })->with('items')->get();

        if ($pembelians->isEmpty()) {
            $this->info("✅ Tidak ada pembelian dengan total Rp 0");
            return;
        }

        $this->info("Found {$pembelians->count()} pembelian with Rp 0\n");

        $withItems = 0;
        $withoutItems = 0;

        foreach ($pembelians as $p) {
            $itemCount = $p->items->count();

            if ($itemCount > 0) {
                $withItems++;
                $this->line("ID {$p->id} ({$p->po_no}): {$itemCount} items - CAN BE FIXED");
            } else {
                $withoutItems++;
                $this->error("ID {$p->id} ({$p->po_no}): NO ITEMS - CANNOT FIX");
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("✅ With Items (can fix): {$withItems}");
        $this->error("❌ Without Items (cannot fix): {$withoutItems}");

        if ($withItems > 0) {
            $this->newLine();
            if ($this->confirm('Fix all pembelian yang punya items?', true)) {
                $this->fixAll($pembelians);
            }
        }

        if ($withoutItems > 0) {
            $this->newLine();
            $this->warn("⚠️ Ada {$withoutItems} pembelian tanpa items!");
            $this->warn("Ini harus diperbaiki secara manual atau dihapus.");

            if ($this->confirm('Tampilkan detail pembelian tanpa items?', false)) {
                foreach ($pembelians as $p) {
                    if ($p->items->count() == 0) {
                        $this->line("\nID: {$p->id}");
                        $this->line("PO: {$p->po_no}");
                        $this->line("Date: {$p->invoice_date}");
                        $this->line("Supplier ID: {$p->supplier_id}");
                    }
                }
            }
        }
    }

    private function fixAll($pembelians)
    {
        $bar = $this->output->createProgressBar($pembelians->count());
        $bar->start();

        $fixed = 0;
        $skipped = 0;

        foreach ($pembelians as $pembelian) {
            if ($pembelian->items->count() == 0) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $totalGross = 0;
            $totalDiscount = 0;

            foreach ($pembelian->items as $item) {
                $itemGross = $item->qty * $item->buy_price;
                $itemDisc = ($item->disc_amount ?? 0) + ($item->disc_nominal ?? 0);

                $totalGross += $itemGross;
                $totalDiscount += $itemDisc;
            }

            $grossCalculated = $totalGross - $totalDiscount;
            $taxAmount = ($grossCalculated * ($pembelian->tax_percent ?? 0)) / 100;
            $netTotal = $grossCalculated + $taxAmount + ($pembelian->extra_cost ?? 0);

            $pembelian->update([
                'gross' => $grossCalculated,
                'discount_total' => $totalDiscount,
                'tax_amount' => $taxAmount,
                'net_total' => $netTotal,
            ]);

            $fixed++;
            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info("✅ Fixed: {$fixed} pembelian");
        $this->warn("⏭️ Skipped: {$skipped} pembelian (no items)");
    }
}
