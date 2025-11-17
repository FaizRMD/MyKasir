<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;

// MODELS VIEW (read-only)
use App\Models\Reports\SalesReport;
use App\Models\Reports\SalesItemReport;

// Masih pakai Sale asli untuk halaman detail
use App\Models\Sale;

class SalesReportController extends Controller
{
    /** Format number for output */
    private function nf($value): string
    {
        return number_format((float)$value, 2, '.', ',');
    }

    /** Laporan per transaksi (header-level, dari VIEW sales_reports) */
    public function index(Request $request): View|Factory|Application
    {
        $filters = [
            'date_from'      => $request->get('date_from'),
            'date_to'        => $request->get('date_to'),
            'user_id'        => $request->integer('user_id'),
            'customer_id'    => $request->integer('customer_id'),
            'payment_method' => $request->get('payment_method', ''),
            'q'              => $request->get('q', ''),
        ];

        $sort = $request->get('sort', 'sale_date_desc');
        $sortMap = [
            'sale_date_desc'    => ['sale_date', 'desc'],
            'sale_date_asc'     => ['sale_date', 'asc'],
            'grand_total_desc'  => ['grand_total', 'desc'],
            'grand_total_asc'   => ['grand_total', 'asc'],
        ];
        [$col, $dir] = $sortMap[$sort] ?? ['sale_date', 'desc'];

        // Base query untuk list & summary
        $base = SalesReport::query()->filter($filters);

        // List
        $sales = (clone $base)->orderBy($col, $dir)->paginate(15)->withQueryString();

        // Summary (bebas dari limit/offset/order)
        $summaryRow = (clone $base)
            ->selectRaw('
                COALESCE(SUM(grand_total),0)    as grand_total_sum,
                COALESCE(SUM(discount_total),0) as discount_sum,
                COALESCE(SUM(tax_total),0)      as tax_sum,
                COUNT(*)                        as trx_count
            ')
            ->first();

        // Total qty item → ambil dari VIEW item; sengaja tidak ikut keyword "q"
        $qtyFilters = $filters; unset($qtyFilters['q']);
        $qtySum = SalesItemReport::query()->filter($qtyFilters)->sum('qty');

        return view('reports.sales.index', [
            'sales'   => $sales,
            'summary' => (object)[
                'grand_total_sum' => $summaryRow?->grand_total_sum ?? 0,
                'discount_sum'    => $summaryRow?->discount_sum    ?? 0,
                'tax_sum'         => $summaryRow?->tax_sum         ?? 0,
                'trx_count'       => $summaryRow?->trx_count       ?? 0,
                'qty_sum'         => $qtySum ?? 0,
            ],
            'filters' => $filters + ['sort' => $sort],
        ]);
    }

    /** Laporan per item (detail-level, dari VIEW sales_item_reports) */
    public function items(Request $request): View|Factory|Application
    {
        $filters = [
            'date_from'      => $request->get('date_from'),
            'date_to'        => $request->get('date_to'),
            'user_id'        => $request->integer('user_id'),
            'customer_id'    => $request->integer('customer_id'),
            'payment_method' => $request->get('payment_method', ''),
            'product_id'     => $request->integer('product_id'),
            'q'              => $request->get('q', ''),
        ];

        $sort = $request->get('sort', 'date_desc');
        $sortMap = [
            'date_desc'  => ['item_date', 'desc'],
            'date_asc'   => ['item_date', 'asc'],
            'total_desc' => ['total', 'desc'],
            'total_asc'  => ['total', 'asc'],
            'qty_desc'   => ['qty', 'desc'],
            'qty_asc'    => ['qty', 'asc'],
        ];
        [$col, $dir] = $sortMap[$sort] ?? ['item_date', 'desc'];

        $base = SalesItemReport::query()->filter($filters);

        $items = (clone $base)->orderBy($col, $dir)->paginate(20)->withQueryString();

        $summaryRow = (clone $base)
            ->selectRaw('COALESCE(SUM(qty),0) as qty_sum, COALESCE(SUM(total),0) as total_sum')
            ->first();

        return view('reports.sales.items', [
            'items'   => $items,
            'summary' => (object)[
                'qty_sum'   => $summaryRow?->qty_sum   ?? 0,
                'total_sum' => $summaryRow?->total_sum ?? 0,
            ],
            'filters' => $filters + ['sort' => $sort],
        ]);
    }

    /** Detail transaksi (pakai model transaksi asli agar bisa load items lengkap) */
    public function show(Sale $sale): View|Factory|Application
    {
        $sale->load([
            'customer:id,name',
            'user:id,name',
            'items:id,sale_id,product_id,name,qty,price,tax_percent,total,batch_no,created_at',
            'items.product:id,name',
        ]);

        return view('reports.sales.show', compact('sale'));
    }

    /** Export CSV: header-level (VIEW sales_reports) */
    public function exportSalesCsv(Request $request): StreamedResponse
    {
        $filters = [
            'date_from'      => $request->get('date_from'),
            'date_to'        => $request->get('date_to'),
            'user_id'        => $request->integer('user_id'),
            'customer_id'    => $request->integer('customer_id'),
            'payment_method' => $request->get('payment_method', ''),
            'q'              => $request->get('q', ''),
        ];

        $query = SalesReport::query()->filter($filters)->orderBy('sale_date', 'desc');

        $filename = 'laporan-penjualan-' . now()->format('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        $columns = ['Tanggal', 'Invoice', 'Customer', 'Kasir', 'Metode', 'Jumlah Item', 'Subtotal Item', 'Diskon (Trx)', 'Pajak (Trx)', 'Grand Total'];

        return response()->stream(function () use ($query, $columns) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($out, $columns);

            $query->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->sale_date)->format('Y-m-d H:i'),
                        $r->invoice_no,
                        $r->customer_name,
                        $r->cashier_name,
                        $r->payment_method,
                        (int) $r->items_count,
                        $this->nf($r->items_total),
                        $this->nf($r->discount_total),
                        $this->nf($r->tax_total),
                        $this->nf($r->grand_total),
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    /** Export CSV: item-level (VIEW sales_item_reports) */
    public function exportItemsCsv(Request $request): StreamedResponse
    {
        $filters = [
            'date_from'      => $request->get('date_from'),
            'date_to'        => $request->get('date_to'),
            'user_id'        => $request->integer('user_id'),
            'customer_id'    => $request->integer('customer_id'),
            'payment_method' => $request->get('payment_method', ''),
            'product_id'     => $request->integer('product_id'),
            'q'              => $request->get('q', ''),
        ];

        $query = SalesItemReport::query()->filter($filters)->orderBy('item_date', 'desc');

        $filename = 'laporan-item-penjualan-' . now()->format('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        $columns = ['Tanggal', 'Invoice', 'Customer', 'Kasir', 'Metode', 'Produk', 'Qty', 'Harga', 'Pajak (%)', 'Total', 'Batch'];

        return response()->stream(function () use ($query, $columns) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($out, $columns);

            $query->chunk(2000, function ($rows) use ($out) {
                foreach ($rows as $it) {
                    fputcsv($out, [
                        optional($it->item_date)->format('Y-m-d H:i'),
                        $it->invoice_no,
                        $it->customer_name,
                        $it->cashier_name,
                        $it->payment_method,
                        $it->product_name,
                        (int) $it->qty,
                        $this->nf($it->price),
                        $this->nf($it->tax_percent),
                        $this->nf($it->total),
                        $it->batch_no,
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    /** Export PDF: header-level (VIEW sales_reports) */
    public function exportSalesPdf(Request $request)
    {
        $filters = [
            'date_from'      => $request->get('date_from'),
            'date_to'        => $request->get('date_to'),
            'user_id'        => $request->integer('user_id'),
            'customer_id'    => $request->integer('customer_id'),
            'payment_method' => $request->get('payment_method',''),
            'q'              => $request->get('q',''),
        ];

        $rows = \App\Models\Reports\SalesReport::query()
            ->filter($filters)
            ->orderBy('sale_date','desc')
            ->get();

        $summary = (clone \App\Models\Reports\SalesReport::query()->filter($filters))
            ->selectRaw('
                COALESCE(SUM(grand_total),0)    as grand_total_sum,
                COALESCE(SUM(discount_total),0) as discount_sum,
                COALESCE(SUM(tax_total),0)      as tax_sum,
                COUNT(*)                        as trx_count
            ')->first();

        // ⬇️ sesuaikan ke nama file yang kamu punya
        $pdf = Pdf::loadView('reports.sales.pdf_sales', [
            'rows'    => $rows,
            'summary' => $summary,
            'filters' => $filters,
            'title'   => 'Laporan Penjualan',
        ])->setPaper('a4','landscape');

        return $pdf->download('laporan-penjualan-'.now()->format('Ymd_His').'.pdf');
    }
    /** Export PDF: item-level (VIEW sales_item_reports) */
    public function exportItemsPdf(Request $request)
    {
        $filters = [
            'date_from'      => $request->get('date_from'),
            'date_to'        => $request->get('date_to'),
            'user_id'        => $request->integer('user_id'),
            'customer_id'    => $request->integer('customer_id'),
            'payment_method' => $request->get('payment_method',''),
            'product_id'     => $request->integer('product_id'),
            'q'              => $request->get('q',''),
        ];

        $title = 'Laporan Item Penjualan';

        $base = \App\Models\Reports\SalesItemReport::query()->filter($filters);

        $rows = (clone $base)->orderBy('item_date','desc')->get();

        $summary = (clone $base)
            ->selectRaw('COALESCE(SUM(qty),0) as qty_sum, COALESCE(SUM(total),0) as total_sum')
            ->first();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sales.pdf_items', [
                'title'   => $title,
                'rows'    => $rows,     // biar konsisten
                'items'   => $rows,     // kompatibel dengan view lama yang pakai $items
                'summary' => $summary,
                'filters' => $filters,
            ])
            ->setPaper('a4','portrait')
            ->download('laporan-item-penjualan-'.now()->format('Ymd_His').'.pdf');
    }


}
