<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'qty',
        'qty_received',   // tracking progress penerimaan
        'uom',            // opsional: satuan
        'cost',
        'discount',
        'tax_pct',        // opsional
        'subtotal',       // qty*cost - discount + tax
    ];

    protected $casts = [
        'qty'          => 'integer',
        'qty_received' => 'integer',
        'cost'         => 'decimal:2',
        'discount'     => 'decimal:2',
        'tax_pct'      => 'decimal:2',
        'subtotal'     => 'decimal:2',
    ];

    protected $attributes = [
        'qty'          => 0,
        'qty_received' => 0,
        'discount'     => 0,
        'tax_pct'      => 0,
        'subtotal'     => 0,
    ];

    // Jika ingin header Purchase otomatis tersentuh saat item berubah:
    // protected $touches = ['purchase'];

    /* =======================
     * Relationships
     * ======================= */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceiptItems()
    {
        return $this->hasMany(GoodsReceiptItem::class, 'purchase_item_id');
    }

    /* =======================
     * Guards & recompute
     * ======================= */
    protected static function booted(): void
    {
        // Validasi & hitung subtotal sebelum simpan
        static::saving(function (self $m) {
            // Normalisasi angka
            $m->qty          = max(0, (int) $m->qty);
            $m->qty_received = max(0, min((int) $m->qty_received, (int) $m->qty)); // 0 ≤ qty_received ≤ qty
            $m->cost         = max(0, (float) $m->cost);
            $m->discount     = max(0, (float) ($m->discount ?? 0));
            $m->tax_pct      = max(0, (float) ($m->tax_pct ?? 0));

            // Hitung ulang subtotal bila field relevan berubah atau belum ada
            if ($m->isDirty(['qty','cost','discount','tax_pct']) || $m->subtotal === null) {
                $m->recomputeSubtotal(false); // false => jangan save lagi (hindari loop)
            }
        });
    }

    /* =======================
     * Scopes
     * ======================= */
    public function scopeOutstanding($q)
    {
        return $q->whereColumn('qty_received', '<', 'qty');
    }

    public function scopeFullyReceived($q)
    {
        return $q->whereColumn('qty_received', '>=', 'qty');
    }

    /* =======================
     * Accessors / Helpers
     * ======================= */

    // Sisa yang belum diterima untuk line ini
    public function getOutstandingQtyAttribute(): int
    {
        $out = (int) ($this->qty ?? 0) - (int) ($this->qty_received ?? 0);
        return $out > 0 ? $out : 0;
    }

    // Harga unit setelah diskon & pajak (informasi saja)
    public function getUnitCostAfterDiscountTaxAttribute(): float
    {
        $qty     = max(1, (int) ($this->qty ?: 1));
        $before  = max(0, ($qty * (float)$this->cost) - (float)$this->discount);
        $tax     = $before * ((float)$this->tax_pct / 100);
        $sub     = $before + $tax;
        return (float) ($sub / $qty);
    }

    /**
     * Hitung ulang subtotal.
     * @param bool $save  true = langsung saveQuietly(), false = hanya set di memory (untuk hook saving)
     */
    public function recomputeSubtotal(bool $save = true): void
    {
        $qty      = (int) ($this->qty ?? 0);
        $cost     = (float) ($this->cost ?? 0);
        $discount = (float) ($this->discount ?? 0);
        $before   = max(0, $qty * $cost - $discount);
        $taxPct   = (float) ($this->tax_pct ?? 0);
        $tax      = $before * ($taxPct / 100);

        $this->subtotal = $before + $tax;

        if ($save) {
            $this->saveQuietly();
        }
    }
}
