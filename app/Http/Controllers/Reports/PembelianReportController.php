<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PembelianReportController extends Controller
{
    /**
     * Display pembelian report with filters
     */
    public function index(Request $request)
    {
        $query = Pembelian::with(['supplier', 'warehouse'])
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        // Filter pencarian
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('invoice_no', 'like', "%{$keyword}%")
                    ->orWhere('po_no', 'like', "%{$keyword}%")
                    ->orWhere('notes', 'like', "%{$keyword}%")
                    ->orWhereHas('supplier', function ($s) use ($keyword) {
                        $s->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        // Filter tipe pembayaran
        if ($request->filled('payment_type')) {
            $query->where('payment_type', strtoupper($request->payment_type));
        }

        // Filter tanggal
        if ($request->filled('from')) {
            $query->whereDate('invoice_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('invoice_date', '<=', $request->to);
        }

        // Filter supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter cashbook
        if ($request->filled('cashbook')) {
            $query->where('cashbook', $request->cashbook);
        }

        $pembelians = $query->paginate(15)->withQueryString();

        return view('reports.pembelian.index', compact('pembelians'));
    }

    /**
     * Show detailed pembelian report
     */
    public function show(Pembelian $pembelian)
    {
        $pembelian->loadMissing([
            'supplier',
            'warehouse',
            'items.product'
        ]);

        // Hitung statistik detail
        $stats = [
            'total_items' => $pembelian->items->count(),
            'total_qty' => $pembelian->items->sum('qty'),
            'subtotal_before_discount' => $pembelian->items->sum(function ($item) {
                return $item->qty * $item->buy_price;
            }),
            'total_discount' => $pembelian->discount_total,
            'gross' => $pembelian->gross,
            'tax_amount' => $pembelian->tax_amount,
            'extra_cost' => $pembelian->extra_cost,
            'net_total' => $pembelian->net_total,
        ];

        return view('reports.pembelian.show', compact('pembelian', 'stats'));
    }

    /**
     * Export to PDF
     */
    public function exportPdf()
    {
        $pembelians = Pembelian::with(['supplier', 'warehouse'])
            ->withCount('items')
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Calculate totals
        $totals = [
            'count' => $pembelians->count(),
            'total_items' => $pembelians->sum('items_count'),
            'total_amount' => $pembelians->sum('net_total'),
            'total_discount' => $pembelians->sum('discount_total'),
            'total_tax' => $pembelians->sum('tax_amount'),
        ];

        $pdf = Pdf::loadView('reports.pembelian.pdf', compact('pembelians', 'totals'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->download('laporan-pembelian-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export to Excel
     */
    public function exportExcel()
    {
        $pembelians = Pembelian::with(['supplier', 'warehouse'])
            ->withCount('items')
            ->orderBy('invoice_date', 'desc')
            ->get();

        return Excel::download(
            new PembelianExport($pembelians),
            'laporan-pembelian-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Get pembelian statistics for dashboard
     */
    public function statistics(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $stats = [
            'total_pembelian' => Pembelian::whereBetween('invoice_date', [$from, $to])->count(),
            'total_amount' => Pembelian::whereBetween('invoice_date', [$from, $to])->sum('net_total'),
            'by_payment_type' => Pembelian::whereBetween('invoice_date', [$from, $to])
                ->select('payment_type', DB::raw('count(*) as count'), DB::raw('sum(net_total) as amount'))
                ->groupBy('payment_type')
                ->get(),
            'by_supplier' => Pembelian::whereBetween('invoice_date', [$from, $to])
                ->with('supplier:id,name')
                ->select('supplier_id', DB::raw('count(*) as count'), DB::raw('sum(net_total) as amount'))
                ->groupBy('supplier_id')
                ->orderByDesc('amount')
                ->limit(10)
                ->get(),
            'by_warehouse' => Pembelian::whereBetween('invoice_date', [$from, $to])
                ->with('warehouse:id,name')
                ->select('warehouse_id', DB::raw('count(*) as count'), DB::raw('sum(net_total) as amount'))
                ->groupBy('warehouse_id')
                ->orderByDesc('amount')
                ->get(),
            'by_cashbook' => Pembelian::whereBetween('invoice_date', [$from, $to])
                ->select('cashbook', DB::raw('count(*) as count'), DB::raw('sum(net_total) as amount'))
                ->whereNotNull('cashbook')
                ->groupBy('cashbook')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get detailed items report
     */
    public function itemsReport(Request $request)
    {
        $query = DB::table('pembelian_items')
            ->join('pembelian', 'pembelian_items.pembelian_id', '=', 'pembelian.id')
            ->join('products', 'pembelian_items.product_id', '=', 'products.id')
            ->join('suppliers', 'pembelian.supplier_id', '=', 'suppliers.id')
            ->select(
                'products.name as product_name',
                'products.code as product_code',
                'suppliers.name as supplier_name',
                'pembelian.invoice_no',
                'pembelian.invoice_date',
                'pembelian_items.qty',
                'pembelian_items.uom',
                'pembelian_items.buy_price',
                'pembelian_items.disc_percent',
                'pembelian_items.disc_amount',
                'pembelian_items.subtotal',
                'pembelian_items.batch_no',
                'pembelian_items.exp_date',
                DB::raw('(pembelian_items.qty * pembelian_items.buy_price) as gross_amount')
            )
            ->orderByDesc('pembelian.invoice_date');

        // Filter by date range
        if ($request->filled('from')) {
            $query->whereDate('pembelian.invoice_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('pembelian.invoice_date', '<=', $request->to);
        }

        // Filter by payment type
        if ($request->filled('payment_type')) {
            $query->where('pembelian.payment_type', strtoupper($request->payment_type));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('pembelian_items.product_id', $request->product_id);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('pembelian.supplier_id', $request->supplier_id);
        }

        $items = $query->paginate(20)->withQueryString();

        return view('reports.pembelian.items', compact('items'));
    }

    /**
     * Hutang (Payable) Report
     */
    public function hutangReport(Request $request)
    {
        $query = Pembelian::with(['supplier', 'warehouse'])
            ->where('payment_type', 'HUTANG')
            ->orderByDesc('due_date')
            ->orderByDesc('invoice_date');

        // Filter status hutang
        if ($request->filled('status')) {
            $today = now()->toDateString();
            if ($request->status === 'overdue') {
                $query->whereDate('due_date', '<', $today);
            } elseif ($request->status === 'upcoming') {
                $query->whereDate('due_date', '>=', $today)
                    ->whereDate('due_date', '<=', now()->addDays(7)->toDateString());
            }
        }

        // Filter tanggal jatuh tempo
        if ($request->filled('due_from')) {
            $query->whereDate('due_date', '>=', $request->due_from);
        }

        if ($request->filled('due_to')) {
            $query->whereDate('due_date', '<=', $request->due_to);
        }

        $hutangs = $query->paginate(15)->withQueryString();

        // Calculate summary
        $summary = [
            'total' => $hutangs->sum('net_total'),
            'overdue' => Pembelian::where('payment_type', 'HUTANG')
                ->whereDate('due_date', '<', now()->toDateString())
                ->sum('net_total'),
            'upcoming' => Pembelian::where('payment_type', 'HUTANG')
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                ->sum('net_total'),
        ];

        return view('reports.pembelian.hutang', compact('hutangs', 'summary'));
    }
}

/**
 * Export Class untuk Excel - Simple & Clean
 */
class PembelianExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected $pembelians;

    public function __construct($pembelians)
    {
        $this->pembelians = $pembelians;
    }

    public function collection()
    {
        return $this->pembelians->map(function ($pembelian, $index) {
            return [
                $index + 1,
                $pembelian->po_no,
                $pembelian->invoice_no ?? '-',
                \Carbon\Carbon::parse($pembelian->invoice_date)->format('d M Y'),
                $pembelian->supplier->name ?? '-',
                $pembelian->warehouse->name ?? '-',
                $pembelian->payment_type,
                $pembelian->items_count ?? 0,
                $pembelian->discount_total ?? 0,
                $pembelian->tax_amount ?? 0,
                $pembelian->net_total,
            ];
        });
    }

    public function headings(): array
    {
        $totalTransaksi = $this->pembelians->count();
        $totalNilai = $this->pembelians->sum('net_total');

        return [
            ['LAPORAN PEMBELIAN'],
            ['Tanggal Export: ' . now()->format('d M Y H:i')],
            ['Total Transaksi: ' . $totalTransaksi . ' | Total Nilai: Rp ' . number_format($totalNilai, 0, ',', '.')],
            [''],
            ['No', 'No. PO', 'No. Invoice', 'Tanggal', 'Supplier', 'Gudang', 'Tipe Bayar', 'Jml Item', 'Diskon', 'Pajak', 'Total']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style title
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '800020']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]
        ]);

        // Style tanggal export
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Style summary
        $sheet->mergeCells('A3:K3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Style table headers
        $sheet->getStyle('A5:K5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '800020']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ]);

        // Auto height for header row
        $sheet->getRowDimension(5)->setRowHeight(25);

        // Number format for currency columns
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('I6:K' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Center align untuk kolom tertentu
        $sheet->getStyle('A6:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H6:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to data
        $sheet->getStyle('A5:K' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Alternate row colors
        for ($row = 6; $row <= $lastRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9F9F9']
                    ]
                ]);
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 18,  // No. PO
            'C' => 18,  // No. Invoice
            'D' => 14,  // Tanggal
            'E' => 28,  // Supplier
            'F' => 22,  // Gudang
            'G' => 14,  // Tipe Bayar
            'H' => 10,  // Jml Item
            'I' => 16,  // Diskon
            'J' => 16,  // Pajak
            'K' => 18,  // Total
        ];
    }

    public function title(): string
    {
        return 'Laporan Pembelian';
    }
}
