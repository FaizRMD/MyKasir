<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class SalesItemReport extends Model
{
    protected $table = 'sales_item_reports';
    protected $primaryKey = 'sale_item_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'sale_item_id'  => 'integer',
        'item_date'     => 'datetime',
        'sale_id'       => 'integer',
        'product_id'    => 'integer',
        'cashier_id'    => 'integer',
        'customer_id'   => 'integer',
        'qty'           => 'integer',
        'price'         => 'decimal:2',
        'tax_percent'   => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    /** Filter sesuai kebutuhan controller */
    public function scopeFilter($q, array $f)
    {
        return $q
            ->when($f['date_from'] ?? null, fn($qq, $v) => $qq->whereDate('item_date', '>=', $v))
            ->when($f['date_to']   ?? null, fn($qq, $v) => $qq->whereDate('item_date', '<=', $v))
            ->when($f['user_id']   ?? null, fn($qq, $v) => $qq->where('cashier_id', $v))
            ->when($f['customer_id'] ?? null, fn($qq, $v) => $qq->where('customer_id', $v))
            ->when(($f['payment_method'] ?? '') !== '', fn($qq, $v) => $qq->where('payment_method', $v))
            ->when($f['product_id'] ?? null, fn($qq, $v) => $qq->where('product_id', $v))
            ->when($f['q'] ?? null, fn($qq, $v) => $qq->where('product_name', 'like', "%{$v}%"));
    }

    /** Proteksi write (opsional) */
    public function save(array $options = []) { return false; }
    public function delete() { return false; }
}
