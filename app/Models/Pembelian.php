<?php

namespace App\Models;

/**
 * @mixin \Eloquent
 * @property int $id
 * @property int|null $supplier_id
 * @property string|null $po_no
 * @property string|null $invoice_no
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $invoice_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property float|null $gross
 * @property float|null $discount_total
 * @property float|null $tax_percent
 * @property float|null $tax_amount
 * @property float|null $extra_cost
 * @property float|null $net_total
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Pembelian extends Model
{
    protected $table = 'pembelian';

    protected $fillable = [
        'po_no',
        'invoice_no',
        'invoice_date',
        'supplier_id',
        'warehouse_id',
        'payment_type',
        'cashbook',
        'due_date',
        'gross',
        'discount_total',
        'tax_percent',
        'tax_amount',
        'extra_cost',
        'net_total',
        'notes',
        'status',
    ];

    protected $casts = [
        'invoice_date'   => 'date',
        'due_date'       => 'date',
        'gross'          => 'float',
        'discount_total' => 'float',
        'tax_percent'    => 'float',
        'tax_amount'     => 'float',
        'extra_cost'     => 'float',
        'net_total'      => 'float',
    ];

    // ========== RELATION ==========

    public function items(): HasMany
    {
        return $this->hasMany(PembelianItem::class, 'pembelian_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function payable(): HasOne
    {
        return $this->hasOne(Payable::class, 'pembelian_id');
    }

    // ========== HELPER FORMAT ==========

    private function asFloat($v): float
    {
        return $v !== null ? (float) $v : 0.0;
    }

    public function getFormattedNetTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->asFloat($this->net_total), 0, ',', '.');
    }

    // ========== HITUNG ULANG TOTAL ==========

    public function recalculateTotalsFromItems(bool $save = true): array
    {
        $this->loadMissing('items');

        $totalGross    = 0.0;
        $totalDiscount = 0.0;

        foreach ($this->items as $item) {
            $qty         = (float) $item->qty;
            $buyPrice    = (float) $item->buy_price;
            $discAmount  = (float) ($item->disc_amount ?? 0);
            $discNominal = (float) ($item->disc_nominal ?? 0);

            $itemGross    = $qty * $buyPrice;
            $itemDiscount = $discAmount + $discNominal;

            $totalGross    += $itemGross;
            $totalDiscount += $itemDiscount;
        }

        $gross      = $totalGross - $totalDiscount;
        $taxPercent = (float) ($this->tax_percent ?? 0);
        $taxAmount  = ($gross * $taxPercent) / 100;
        $extraCost  = (float) ($this->extra_cost ?? 0);
        $netTotal   = $gross + $taxAmount + $extraCost;

        if ($save) {
            $this->gross          = $gross;
            $this->discount_total = $totalDiscount;
            $this->tax_amount     = $taxAmount;
            $this->net_total      = $netTotal;
            $this->save();
        }

        return [
            'total_gross'    => $totalGross,
            'total_discount' => $totalDiscount,
            'gross'          => $gross,
            'tax_percent'    => $taxPercent,
            'tax_amount'     => $taxAmount,
            'extra_cost'     => $extraCost,
            'net_total'      => $netTotal,
        ];
    }
}
