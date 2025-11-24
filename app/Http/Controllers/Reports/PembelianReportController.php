<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;

class PembelianReportController extends Controller
{
    /**
     * Halaman utama laporan pembelian (list + filter)
     */
    public function index(Request $request)
    {
        $query = Pembelian::with(['supplier', 'warehouse'])
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        // FILTER PENCARIAN
        if ($search = trim($request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('po_no', 'like', "%{$search}%")
                  ->orWhere('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($qs) use ($search) {
                      $qs->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // FILTER TIPE PEMBAYARAN
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // FILTER TANGGAL
        if ($request->filled('from')) {
            $query->whereDate('invoice_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('invoice_date', '<=', $request->to);
        }

        $pembelians = $query->paginate(15)->withQueryString();

        // 🔧 AUTO FIX TOTAL KOSONG (data lama)
        $pembelians->getCollection()->each(function (Pembelian $pembelian) {
            if ((float) $pembelian->net_total === 0.0) {
                // hitung ulang dari items & langsung simpan
                $pembelian->recalculateTotalsFromItems(true);
            }
        });

        return view('reports.pembelian.index', compact('pembelians'));
    }

    /**
     * Halaman detail satu pembelian (dipanggil dari /reports/pembelian/{id})
     */
    public function show(Pembelian $pembelian)
    {
        // Pastikan relasi yang dibutuhkan ter-load
        $pembelian->load([
            'supplier:id,name',
            'warehouse:id,name',
            'items.product:id,name,code,sku',
        ]);

        // Ringkasan angka untuk ditampilkan di header/detail
        $summary = [
            'total_items' => $pembelian->items->count(),
            'total_qty'   => (float) $pembelian->items->sum('qty'),
            'gross'       => (float) $pembelian->gross,
            'discount'    => (float) $pembelian->discount_total,
            'tax'         => (float) $pembelian->tax_amount,
            'extra_cost'  => (float) $pembelian->extra_cost,
            'net_total'   => (float) $pembelian->net_total,
        ];

        return view('reports.pembelian.show', compact('pembelian', 'summary'));
    }
}
