<?php

namespace App\Services\Purchasing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierReportService
{
    public function summary(array $filters)
    {
        $dateExpr = DB::raw('COALESCE(purchases.tanggal, DATE(purchases.created_at))');

        $usePayments = Schema::hasTable('supplier_payments');

        if ($usePayments) {
            $paymentsSub = DB::table('supplier_payments as sp')
                ->select('sp.supplier_id', DB::raw('SUM(sp.amount) as total_payment'))
                ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('sp.payment_date', '>=', $d))
                ->when($filters['date_to']   ?? null, fn($q, $d) => $q->whereDate('sp.payment_date', '<=', $d))
                ->groupBy('sp.supplier_id');
        }

        $q = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->when($usePayments, fn($x) => $x->leftJoinSub($paymentsSub, 'P', 'P.supplier_id', '=', 'suppliers.id'))
            ->when($filters['supplier_ids'] ?? null, fn($x, $ids) => $x->whereIn('purchases.supplier_id', $ids))
            ->when($filters['date_from'] ?? null, fn($x, $d) => $x->whereDate($dateExpr, '>=', $d))
            ->when($filters['date_to']   ?? null, fn($x, $d) => $x->whereDate($dateExpr, '<=', $d))
            ->selectRaw('
                purchases.supplier_id,
                suppliers.name as supplier_name,
                COUNT(DISTINCT purchases.id) as total_invoices,
                COALESCE(SUM(purchases.total),0) as total_purchase
            ');

        // ⬇️ Tambahkan kolom payment TANPA koma di depan
        if ($usePayments) {
            $q->addSelect(DB::raw('COALESCE(P.total_payment,0) as total_payment'));
        } else {
            $q->addSelect(DB::raw('0 as total_payment'));
        }

        // Group by cukup kolom non-aggregat
        $q->groupBy('purchases.supplier_id', 'suppliers.name');

        if (!empty($filters['min_total'])) {
            $q->havingRaw('COALESCE(SUM(purchases.total),0) >= ?', [$filters['min_total']]);
        }
        if (!empty($filters['max_total'])) {
            $q->havingRaw('COALESCE(SUM(purchases.total),0) <= ?', [$filters['max_total']]);
        }

        $rows = $q->orderBy('suppliers.name')->get();

        return $rows->map(function ($r) {
            $r->outstanding = (float)($r->total_purchase ?? 0) - (float)($r->total_payment ?? 0);
            return $r;
        });
    }
}
