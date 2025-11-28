<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class LaporanExpiredController extends Controller
{
    /**
     * Tampilkan halaman laporan obat expired
     */
    public function index(Request $request)
    {
        $filterStatus = $request->get('status', 'semua');
        $filterDays = (int) $request->get('days', 30);
        $search = $request->get('search', '');

        $today = Carbon::today();
        $futureDate = Carbon::today()->addDays($filterDays);

        $query = DB::table('pembelian_items as pi')
            ->leftJoin('products as p', 'p.id', '=', 'pi.product_id')
            ->leftJoin('pembelian as pm', 'pm.id', '=', 'pi.pembelian_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'pm.supplier_id')
            ->whereNotNull('pi.exp_date')
            ->select(
                'pi.id',
                'pi.product_id',
                'p.sku as product_code',
                'p.name as product_name',
                'pi.batch_no',
                'pi.exp_date',
                'pi.qty',
                'pi.uom',
                'pi.buy_price',
                'pm.invoice_no',
                'pm.invoice_date',
                's.name as supplier_name',
                DB::raw('DATEDIFF(pi.exp_date, CURDATE()) as days_until_expired')
            );

        // Filter status
        switch ($filterStatus) {
            case 'expired':
                $query->whereDate('pi.exp_date', '<', $today);
                break;

            case 'akan_expired':
                $query->whereDate('pi.exp_date', '>=', $today)
                    ->whereDate('pi.exp_date', '<=', $futureDate);
                break;

            case 'aman':
                $query->whereDate('pi.exp_date', '>', $futureDate);
                break;

            case 'semua':
            default:
                // tidak ada filter tambahan
                break;
        }

        // Filter pencarian
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('pi.batch_no', 'like', "%{$search}%");
            });
        }

        // PENTING: gunakan paginate supaya tombol previous/next jalan
        $items = $query
            ->orderBy('pi.exp_date', 'asc')
            ->paginate(5);

        $stats = $this->getExpiredStatistics($filterDays);

        return view('reports.expired.index', compact(
            'items',
            'stats',
            'filterStatus',
            'filterDays',
            'search'
        ));
    }

    /**
     * Statistik ringkasan expired
     */
    private function getExpiredStatistics(int $days = 30): array
    {
        $today = Carbon::today();
        $futureDate = Carbon::today()->addDays($days);

        $baseQuery = DB::table('pembelian_items')
            ->whereNotNull('exp_date');

        $totalExpired = (clone $baseQuery)
            ->whereDate('exp_date', '<', $today)
            ->count();

        $totalWillExpire = (clone $baseQuery)
            ->whereDate('exp_date', '>=', $today)
            ->whereDate('exp_date', '<=', $futureDate)
            ->count();

        $totalSafe = (clone $baseQuery)
            ->whereDate('exp_date', '>', $futureDate)
            ->count();

        $lossValue = (clone $baseQuery)
            ->whereDate('exp_date', '<', $today)
            ->sum(DB::raw('qty * buy_price'));

        return [
            'total_expired' => $totalExpired,
            'total_will_expire' => $totalWillExpire,
            'total_safe' => $totalSafe,
            'loss_value' => $lossValue,
        ];
    }

    /**
     * EXPORT (Excel/PDF)
     */
    public function export(Request $request)
    {
        $filterStatus = $request->get('status', 'semua');
        $filterDays = (int) $request->get('days', 30);
        $search = $request->get('search', '');
        $format = $request->get('format', 'excel');

        $today = Carbon::today();
        $futureDate = Carbon::today()->addDays($filterDays);

        $query = DB::table('pembelian_items as pi')
            ->leftJoin('products as p', 'p.id', '=', 'pi.product_id')
            ->leftJoin('pembelian as pm', 'pm.id', '=', 'pi.pembelian_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'pm.supplier_id')
            ->whereNotNull('pi.exp_date')
            ->select(
                'p.sku as product_code',
                'p.name as product_name',
                'pi.batch_no',
                'pi.exp_date',
                'pi.qty',
                'pi.uom',
                'pi.buy_price',
                'pm.invoice_no',
                'pm.invoice_date',
                's.name as supplier_name',
                DB::raw('DATEDIFF(pi.exp_date, CURDATE()) as days_until_expired'),
                DB::raw('(pi.qty * pi.buy_price) as total_value')
            );

        // Filter status (sama seperti index)
        switch ($filterStatus) {
            case 'expired':
                $query->whereDate('pi.exp_date', '<', $today);
                break;

            case 'akan_expired':
                $query->whereDate('pi.exp_date', '>=', $today)
                    ->whereDate('pi.exp_date', '<=', $futureDate);
                break;

            case 'aman':
                $query->whereDate('pi.exp_date', '>', $futureDate);
                break;

            case 'semua':
            default:
                break;
        }

        // Filter pencarian (sama seperti index)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('pi.batch_no', 'like', "%{$search}%");
            });
        }

        $items = $query
            ->orderBy('pi.exp_date', 'asc')
            ->get();

        if ($format === 'excel') {
            return $this->exportToExcel($items, $filterStatus);
        }

        return $this->exportToPdf($items, $filterStatus);
    }

    /**
     * Export CSV
     */
    private function exportToExcel($items, string $filterStatus)
    {
        $filename = 'laporan_expired_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = static function () use ($items) {
            $file = fopen('php://output', 'w');

            // Header kolom
            fputcsv($file, [
                'Kode Produk',
                'Nama Produk',
                'Batch No',
                'Tanggal Expired',
                'Hari Tersisa',
                'Qty',
                'Satuan',
                'Harga Beli',
                'Total Nilai',
                'Invoice',
                'Tanggal Invoice',
                'Supplier',
                'Status',
            ]);

            // Data
            foreach ($items as $item) {
                $status = $item->days_until_expired < 0
                    ? 'EXPIRED'
                    : ($item->days_until_expired <= 30 ? 'AKAN EXPIRED' : 'AMAN');

                fputcsv($file, [
                    $item->product_code,
                    $item->product_name,
                    $item->batch_no,
                    $item->exp_date,
                    $item->days_until_expired,
                    $item->qty,
                    $item->uom,
                    $item->buy_price,
                    $item->total_value,
                    $item->invoice_no,
                    $item->invoice_date,
                    $item->supplier_name,
                    $status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export PDF
     */
    private function exportToPdf($items, string $filterStatus)
    {
        $pdf = Pdf::loadView('reports.expired.pdf', [
            'items' => $items,
            'filterStatus' => $filterStatus,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ]);

        return $pdf->download('laporan_expired_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * API Notifikasi
     */
    public function getUpcomingExpired(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $today = Carbon::today();
        $futureDate = Carbon::today()->addDays($days);

        $items = DB::table('pembelian_items as pi')
            ->leftJoin('products as p', 'p.id', '=', 'pi.product_id')
            ->whereNotNull('pi.exp_date')
            ->whereDate('pi.exp_date', '>=', $today)
            ->whereDate('pi.exp_date', '<=', $futureDate)
            ->select(
                'p.name as product_name',
                'pi.batch_no',
                'pi.exp_date',
                'pi.qty',
                'pi.uom',
                DB::raw('DATEDIFF(pi.exp_date, CURDATE()) as days_until_expired')
            )
            ->orderBy('pi.exp_date', 'asc')
            ->limit(10)
            ->get();

        return response()->json($items);
    }
}
