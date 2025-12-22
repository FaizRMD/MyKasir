<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\Payable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Laporan Hutang/Payables - Menampilkan daftar hutang yang belum lunas
     */
    public function hutangReport(Request $request)
    {
        $query = Payable::with(['supplier', 'pembelian'])
            ->where(function ($q) {
                $q->where('status', '!=', 'paid')
                  ->where('status', '!=', 'lunas');
            });

        // FILTER PENCARIAN
        if ($search = trim($request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('supplier', function ($qs) use ($search) {
                    $qs->where('name', 'like', "%{$search}%");
                })
                ->orWhere('ref_no', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%");
            });
        }

        // FILTER SUPPLIER
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // FILTER OVERDUE
        if ($request->get('show_overdue') === '1') {
            $query->whereDate('due_date', '<', now());
        }

        // FILTER TANGGAL
        if ($request->filled('from_date')) {
            $query->whereDate('issue_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
        }

        $payables = $query
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('reports.pembelian.hutang', compact('payables'));
    }

    /**
     * Update Status Pembayaran Hutang
     */
    public function updatePayableStatus(Request $request, Payable $payable)
    {
        $validated = $request->validate([
            'status' => 'required|in:unpaid,belum lunas,partial,pending,paid,lunas',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $payable->status = $validated['status'];

        if ($request->filled('paid_amount')) {
            $payable->paid_amount = $validated['paid_amount'];
        }

        // Jika status = paid/lunas, set paid_amount = amount
        if (in_array($validated['status'], ['paid', 'lunas'])) {
            $payable->paid_amount = $payable->amount;
        }

        $payable->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran hutang berhasil diperbarui',
            'data' => $payable->only(['id', 'status', 'paid_amount', 'balance'])
        ]);
    }

    /**
     * Export Laporan Pembelian ke PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Pembelian::with(['supplier', 'warehouse', 'items'])
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

        $pembelians = $query->get();

        // Hitung totals
        $totals = [
            'count' => $pembelians->count(),
            'total_items' => $pembelians->sum(function ($p) { return $p->items->count(); }),
            'total_amount' => $pembelians->sum('net_total'),
            'total_discount' => $pembelians->sum('discount_total'),
            'total_tax' => $pembelians->sum('tax_amount'),
        ];

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pembelian.pdf', compact('pembelians', 'totals'));
        return $pdf->download('laporan_pembelian_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Export Laporan Pembelian ke Excel
     */
    public function exportExcel(Request $request)
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

        $pembelians = $query->get();

        // Export dengan Maatwebsite Excel
        return Excel::download(new \App\Exports\PembelianExport($pembelians), 'laporan_pembelian_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    /**
     * Statistik Pembelian
     */
    public function statistics(Request $request)
    {
        $fromDate = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to', now()->format('Y-m-d'));

        $total = Pembelian::whereBetween('invoice_date', [$fromDate, $toDate])
            ->sum('net_total');

        $count = Pembelian::whereBetween('invoice_date', [$fromDate, $toDate])
            ->count();

        $avgAmount = Pembelian::whereBetween('invoice_date', [$fromDate, $toDate])
            ->avg('net_total');

        return response()->json([
            'total' => $total ?? 0,
            'count' => $count ?? 0,
            'average' => $avgAmount ?? 0,
            'period' => "$fromDate to $toDate"
        ]);
    }

    /**
     * Laporan Item Pembelian
     */
    public function itemsReport(Request $request)
    {
        $query = DB::table('pembelian_items as pi')
            ->join('pembelian as p', 'p.id', '=', 'pi.pembelian_id')
            ->join('products as prod', 'prod.id', '=', 'pi.product_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->select(
                'pi.id',
                'p.invoice_no',
                'p.invoice_date',
                's.name as supplier_name',
                'prod.name as product_name',
                'prod.code as product_code',
                'pi.qty',
                'pi.buy_price',
                'pi.disc_amount',
                'pi.disc_nominal',
                DB::raw('(pi.qty * pi.buy_price - COALESCE(pi.disc_amount, 0) - COALESCE(pi.disc_nominal, 0)) as item_total')
            )
            ->orderByDesc('p.invoice_date')
            ->orderByDesc('pi.id');

        // FILTER PENCARIAN
        if ($search = trim($request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('prod.name', 'like', "%{$search}%")
                  ->orWhere('prod.code', 'like', "%{$search}%")
                  ->orWhere('p.invoice_no', 'like', "%{$search}%");
            });
        }

        // FILTER SUPPLIER
        if ($request->filled('supplier_id')) {
            $query->where('p.supplier_id', $request->supplier_id);
        }

        // FILTER TANGGAL
        if ($request->filled('from')) {
            $query->whereDate('p.invoice_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('p.invoice_date', '<=', $request->to);
        }

        $items = $query->paginate(20)->withQueryString();

        return view('reports.pembelian.items', compact('items'));
    }
}
