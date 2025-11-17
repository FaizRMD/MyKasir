<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierReportRequest;
use App\Models\Supplier;
use App\Services\Purchasing\SupplierReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierReportController extends Controller
{
    public function index(SupplierReportRequest $request, SupplierReportService $service)
    {
        $filters = $request->validated();

        $suppliers = Supplier::query()
            ->select('id', 'name')
            ->active() // kalau tidak ada scope active(), hapus baris ini
            ->orderBy('name')
            ->get();

        $summary = $service->summary($filters);

        // Export CSV (tanpa retur)
        if ($request->get('export') === 'csv') {
            $filename = 'laporan_supplier_' . now()->format('Ymd_His') . '.csv';
            return new StreamedResponse(function () use ($summary) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Supplier', 'Total Invoice', 'Total Pembelian', 'Total Pembayaran', 'Saldo Terbuka']);
                foreach ($summary as $row) {
                    fputcsv($out, [
                        $row->supplier_name,
                        $row->total_invoices,
                        number_format($row->total_purchase, 2, '.', ''),
                        number_format($row->total_payment, 2, '.', ''), // 0 jika tabel payments tidak ada
                        number_format($row->outstanding, 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        // Export PDF (opsional)
        if ($request->get('export') === 'pdf') {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchasing.suppliers.pdf', [
                    'summary' => $summary,
                    'filters' => $filters,
                ])->setPaper('a4', 'portrait');
                return $pdf->download('laporan_supplier_' . now()->format('Ymd_His') . '.pdf');
            }
            return response()->view('purchasing.suppliers.pdf', [
                'summary' => $summary,
                'filters' => $filters,
                'dompdf_missing' => true,
            ]);
        }

        return view('purchasing.suppliers.index', [
            'summary'   => $summary,
            'filters'   => $filters,
            'suppliers' => $suppliers,
        ]);
    }
}
