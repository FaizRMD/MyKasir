<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Ganti sesuai kolom tanggal transaksi penjualan di tabel sales.
     * - 'created_at'  jika pakai timestamp bawaan
     * - 'date'        jika kamu punya kolom tanggal khusus
     */
    private string $saleDateCol = 'created_at';

    /** Render Dashboard (HTML) */
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        $cashierId   = $this->effectiveCashierId($request); // paksa kasir lihat datanya sendiri

        $metrics  = $this->buildMetrics($from, $to, $cashierId);

        // Daftar kasir untuk dropdown (admin/owner bisa pilih)
        $cashiers = User::select('id','name')->orderBy('name')->get();

        return view('dashboard', $metrics + [
            'dateFrom' => $from->toDateString(),
            'dateTo'   => $to->toDateString(),
            'cashiers' => $cashiers,
            // blade kamu pakai request('cashier_id'), jadi tidak perlu override
        ]);
    }

    /** JSON endpoint opsional */
    public function metrics(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        $cashierId   = $this->effectiveCashierId($request);

        $metrics = $this->buildMetrics($from, $to, $cashierId);

        return response()->json($metrics + [
            'dateFrom' => $from->toDateString(),
            'dateTo'   => $to->toDateString(),
        ]);
    }

    /**
     * Tentukan "kasir efektif" untuk filter:
     * - Jika user role = kasir → selalu pakai dirinya sendiri (abaikan dropdown).
     * - Jika admin/owner → pakai pilihan dari dropdown (boleh null = semua kasir).
     */
    private function effectiveCashierId(Request $request): ?int
    {
        if (auth()->check() && auth()->user()->hasRole('kasir')) {
            return (int) auth()->id();
        }
        $id = (int) $request->integer('cashier_id');
        return $id > 0 ? $id : null;
    }

    /** Resolve rentang tanggal dari berbagai pola input di filter bar */
    private function resolveRange(Request $request): array
    {
        $now    = Carbon::now();
        $mode   = $request->input('mode');     // 'monthly' | 'range'
        $preset = $request->input('preset');   // '30d' | 'this-year' | 'last-year'

        $dateFromReq = $request->input('date_from');
        $dateToReq   = $request->input('date_to');

        // Back-compat params
        $days = (int) $request->integer('days', 0);
        $from = $request->get('from');
        $to   = $request->get('to');

        // 1) Preset
        if ($preset === '30d') {
            return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()];
        }
        if ($preset === 'this-year') {
            return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
        }
        if ($preset === 'last-year') {
            $y = $now->copy()->subYear();
            return [$y->copy()->startOfYear(), $y->copy()->endOfYear()];
        }

        // 2) Range eksplisit
        if (($mode === 'range') && $dateFromReq && $dateToReq) {
            $df = Carbon::parse($dateFromReq)->startOfDay();
            $dt = Carbon::parse($dateToReq)->endOfDay();
            if ($df->greaterThan($dt)) {
                [$df, $dt] = [$dt->copy()->startOfDay(), $df->copy()->endOfDay()];
            }
            return [$df, $dt];
        }

        // 3) Monthly
        if ($mode === 'monthly') {
            $month = (int) $request->input('month', $now->month);
            $year  = (int) $request->input('year',  $now->year);
            $df = Carbon::create($year, $month, 1)->startOfDay();
            $dt = $df->copy()->endOfMonth()->endOfDay();
            return [$df, $dt];
        }

        // 4) Fallback explicit from/to
        if ($from && $to) {
            $df = Carbon::parse($from)->startOfDay();
            $dt = Carbon::parse($to)->endOfDay();
            if ($df->greaterThan($dt)) {
                [$df, $dt] = [$dt->copy()->startOfDay(), $df->copy()->endOfDay()];
            }
            return [$df, $dt];
        }

        // 5) Fallback days (default 30)
        $days = max(1, $days ?: 30);
        $dt   = Carbon::today()->endOfDay();
        $df   = (clone $dt)->subDays($days - 1)->startOfDay();
        return [$df, $dt];
    }

    /** Build semua metrik dashboard berdasarkan range & cashier filter (tanpa ubah model/view) */
    private function buildMetrics(Carbon $dateFrom, Carbon $dateTo, ?int $cashierId = null): array
    {
        $saleDateCol = $this->saleDateCol;

        // ===== Master KPI (global)
        $totalProducts  = (int) Product::count();
        $activeProducts = (int) Product::where('is_active', true)->count();
        $suppliersCount = (int) Supplier::count();
        $stockTotal     = (int) Product::sum('stock');
        $stockMinItems  = (int) Product::whereColumn('stock', '<=', 'min_stock')->count();

        // ===== KPI penjualan (terfilter kasir jika dipilih/role kasir)
        $salesInRange = (float) Sale::whereBetween($saleDateCol, [$dateFrom, $dateTo])
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->sum('grand_total');

        $trxInRange = (int) Sale::whereBetween($saleDateCol, [$dateFrom, $dateTo])
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->count();

        // variabel yang dipakai blade kamu
        $salesToday = $salesInRange;
        $trxToday   = $trxInRange;
        $salesMonth = $salesInRange;

        // ===== Grafik penjualan harian (single-series sesuai filter)
        $daily = Sale::selectRaw("DATE($saleDateCol) as d, SUM(grand_total) as total")
            ->whereBetween($saleDateCol, [$dateFrom, $dateTo])
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd')
            ->all();

        $labels = [];
        $series = [];
        $period = CarbonPeriod::create($dateFrom->copy()->startOfDay(), '1 day', $dateTo->copy()->startOfDay());
        foreach ($period as $day) {
            $key      = $day->toDateString();
            $labels[] = $day->isoFormat('D MMM');
            $series[] = (float) ($daily[$key] ?? 0.0);
        }
        if (count($labels) === 1) {
            $labels[] = $dateTo->copy()->addDay()->isoFormat('D MMM');
            $series[] = 0.0;
        }
        $seriesMax = max(1, (int) round((max($series) ?: 0) * 1.1));

        // ===== Pergerakan stok
        // IN: filter langsung di inventory_movements.user_id (jika kamu isi saat stok IN)
        $stockIn = (int) InventoryMovement::where('type', 'IN')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->sum('qty');

        // OUT: gunakan join ke sales (agar dapat kasir dari sale.user_id & tanggal penjualan)
        $stockOut = (int) InventoryMovement::query()
            ->where('inventory_movements.type', 'OUT')
            ->join('sales', 'sales.id', '=', 'inventory_movements.sale_id')
            ->whereBetween("sales.$saleDateCol", [$dateFrom, $dateTo])
            ->when($cashierId, fn($q) => $q->where('sales.user_id', $cashierId))
            ->sum('inventory_movements.qty');

        // ===== Top produk pada range (via relasi ke sales, tanpa ubah model/view)
        $topProducts = SaleItem::select(
                'product_id',
                DB::raw('SUM(qty) as qty'),
                DB::raw('SUM(total) as omzet')
            )
            ->whereHas('sale', function ($q) use ($saleDateCol, $dateFrom, $dateTo, $cashierId) {
                $q->whereBetween($saleDateCol, [$dateFrom, $dateTo]);
                if ($cashierId) $q->where('user_id', $cashierId);
            })
            ->groupBy('product_id')
            ->orderByDesc('omzet')
            ->with('product:id,name,sku')
            ->limit(10)
            ->get();

        return [
            // Master KPI
            'totalProducts'  => $totalProducts,
            'activeProducts' => $activeProducts,
            'suppliersCount' => $suppliersCount,
            'stockTotal'     => $stockTotal,
            'stockMinItems'  => $stockMinItems,

            // KPI range
            'salesToday'     => $salesToday,
            'trxToday'       => $trxToday,
            'salesMonth'     => $salesMonth,

            // Stok (range)
            'stockIn'        => $stockIn,
            'stockOut'       => $stockOut,

            // Chart penjualan harian
            'labels'         => $labels,
            'series'         => $series,
            'seriesMax'      => $seriesMax,

            // Top produk (range)
            'topProducts'    => $topProducts,
        ];
    }
}
