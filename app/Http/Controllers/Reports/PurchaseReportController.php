<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReportController extends Controller
{
    /**
     * Display purchase order report with filters
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'apoteker', 'user'])
            ->withCount('items')
            ->orderByDesc('po_date')
            ->orderByDesc('id');

        // Filter pencarian
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('po_no', 'like', "%{$keyword}%")
                  ->orWhere('note', 'like', "%{$keyword}%")
                  ->orWhereHas('supplier', function ($s) use ($keyword) {
                      $s->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        // Filter tanggal
        if ($request->filled('from')) {
            $query->whereDate('po_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('po_date', '<=', $request->to);
        }

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query->paginate(15)->withQueryString();

        return view('reports.purchases.index', compact('purchases'));
    }

    /**
     * Show detailed purchase order report
     */
    public function show(Purchase $purchase)
    {
        $purchase->loadMissing([
            'supplier',
            'apoteker',
            'user',
            'items.product'
        ]);

        // Hitung statistik detail
        $stats = [
            'total_items' => $purchase->items->count(),
            'total_qty' => $purchase->items->sum('qty'),
            'total_received' => $purchase->items->sum('qty_received'),
            'total_outstanding' => $purchase->items->sum(function($item) {
                return max(0, $item->qty - $item->qty_received);
            }),
            'subtotal' => $purchase->items->sum(function($item) {
                return $item->qty * $item->cost;
            }),
            'total_discount' => $purchase->items->sum('discount'),
            'total_tax' => $purchase->items->sum(function($item) {
                $beforeTax = max(0, ($item->qty * $item->cost) - $item->discount);
                return $beforeTax * ($item->tax_pct / 100);
            }),
        ];

        return view('reports.purchases.show', compact('purchase', 'stats'));
    }

    /**
     * Export purchase report to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Purchase::with(['supplier', 'apoteker'])
            ->withCount('items')
            ->orderByDesc('po_date');

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('from')) {
            $query->whereDate('po_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('po_date', '<=', $request->to);
        }

        $purchases = $query->get();

        // Calculate totals
        $totals = [
            'count' => $purchases->count(),
            'amount' => $purchases->sum('total'),
            'items' => $purchases->sum('items_count'),
        ];

        // TODO: Implement PDF generation
        // return PDF::loadView('reports.purchases.pdf', compact('purchases', 'totals'))
        //     ->download('purchase-report-' . now()->format('Y-m-d') . '.pdf');

        return response()->json([
            'message' => 'PDF Export coming soon',
            'data' => compact('purchases', 'totals')
        ]);
    }

    /**
     * Export purchase report to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Purchase::with(['supplier', 'apoteker'])
            ->withCount('items')
            ->orderByDesc('po_date');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('from')) {
            $query->whereDate('po_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('po_date', '<=', $request->to);
        }

        $purchases = $query->get();

        // TODO: Implement Excel export
        // return Excel::download(new PurchaseExport($purchases),
        //     'purchase-report-' . now()->format('Y-m-d') . '.xlsx');

        return response()->json([
            'message' => 'Excel Export coming soon',
            'count' => $purchases->count()
        ]);
    }

    /**
     * Get purchase statistics for dashboard
     */
    public function statistics(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $stats = [
            'total_po' => Purchase::whereBetween('po_date', [$from, $to])->count(),
            'total_amount' => Purchase::whereBetween('po_date', [$from, $to])->sum('total'),
            'by_status' => Purchase::whereBetween('po_date', [$from, $to])
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total) as amount'))
                ->groupBy('status')
                ->get(),
            'by_supplier' => Purchase::whereBetween('po_date', [$from, $to])
                ->with('supplier:id,name')
                ->select('supplier_id', DB::raw('count(*) as count'), DB::raw('sum(total) as amount'))
                ->groupBy('supplier_id')
                ->orderByDesc('amount')
                ->limit(10)
                ->get(),
            'by_category' => Purchase::whereBetween('po_date', [$from, $to])
                ->select('category', DB::raw('count(*) as count'), DB::raw('sum(total) as amount'))
                ->groupBy('category')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get detailed items report
     */
    public function itemsReport(Request $request)
    {
        $query = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->select(
                'products.name as product_name',
                'products.code as product_code',
                'suppliers.name as supplier_name',
                'purchases.po_no',
                'purchases.po_date',
                'purchase_items.qty',
                'purchase_items.qty_received',
                'purchase_items.cost',
                'purchase_items.subtotal',
                DB::raw('(purchase_items.qty - purchase_items.qty_received) as outstanding')
            )
            ->orderByDesc('purchases.po_date');

        // Filter by date range
        if ($request->filled('from')) {
            $query->whereDate('purchases.po_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('purchases.po_date', '<=', $request->to);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('purchases.status', strtoupper($request->status));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('purchase_items.product_id', $request->product_id);
        }

        $items = $query->paginate(20)->withQueryString();

        return view('reports.purchases.items', compact('items'));
    }
}
