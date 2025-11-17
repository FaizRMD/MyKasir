<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    protected $table = 'sales_reports';
    protected $primaryKey = 'sale_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'sale_id'        => 'integer',
        'sale_date'      => 'datetime',
        'cashier_id'     => 'integer',
        'customer_id'    => 'integer',
        'items_count'    => 'integer',
        'items_total'    => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total'      => 'decimal:2',
        'grand_total'    => 'decimal:2',
    ];

    /** Filter sesuai kebutuhan controller */
    public function scopeFilter($q, array $f)
    {
        return $q
            ->when($f['date_from'] ?? null, fn($qq, $v) => $qq->whereDate('sale_date', '>=', $v))
            ->when($f['date_to']   ?? null, fn($qq, $v) => $qq->whereDate('sale_date', '<=', $v))
            ->when($f['user_id']   ?? null, fn($qq, $v) => $qq->where('cashier_id', $v))
            ->when($f['customer_id'] ?? null, fn($qq, $v) => $qq->where('customer_id', $v))
            ->when(($f['payment_method'] ?? '') !== '', fn($qq, $v) => $qq->where('payment_method', $v))
            ->when($f['q'] ?? null, function ($qq, $v) {
                $qq->where(function ($w) use ($v) {
                    $w->where('invoice_no', 'like', "%{$v}%")
                      ->orWhere('customer_name', 'like', "%{$v}%")
                      ->orWhere('cashier_name',  'like', "%{$v}%");
                });
            });
    }

    /** Proteksi write (opsional) */
    public function save(array $options = []) { return false; }
    public function delete() { return false; }
}
