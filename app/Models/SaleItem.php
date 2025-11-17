<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryMovement;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'name',
        'qty',
        'price',
        'tax_percent',
        'total',
        'batch_no',
    ];

    protected $casts = [
        'qty'         => 'integer',
        'price'       => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    /* ===========================
     |  RELATIONS
     |===========================*/
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /* ===========================
     |  ACCESSORS / MUTATORS
     |===========================*/

    /** Alias agar kode lama yang pakai "subtotal" tetap jalan. */
    public function getSubtotalAttribute()
    {
        return $this->total;
    }
    public function setSubtotalAttribute($value): void
    {
        $this->attributes['total'] = $value;
    }

    /** Total sebelum pajak (kalau butuh di view). */
    public function getTotalBeforeTaxAttribute()
    {
        $qty   = (int) ($this->qty ?? 0);
        $price = (float) ($this->price ?? 0);
        return $qty * $price;
    }

    /* ===========================
     |  SCOPES
     |===========================*/
    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('name', 'like', "%{$term}%")
              ->orWhereHas('sale', fn($s) => $s->where('invoice_no', 'like', "%{$term}%"))
              ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"));
        });
    }

    public function scopeBetween($q, ?string $from, ?string $to)
    {
        if ($from) $q->whereDate('created_at', '>=', $from);
        if ($to)   $q->whereDate('created_at', '<=', $to);
        return $q;
    }

    public function scopeForProduct($q, $productId)
    {
        return $q->where('product_id', $productId);
    }

    /** 🔥 Scope: filter item milik kasir yang sedang login (lewat sale.user_id) */
    public function scopeOwnedByLoggedInCashier($q)
    {
        return $q->whereHas('sale', fn($s) => $s->where('user_id', auth()->id()));
    }

    /** 🔥 Scope: filter item untuk kasir tertentu (ID user) */
    public function scopeFilterByCashier($q, ?int $cashierId)
    {
        return $q->when($cashierId, fn($qq) =>
            $qq->whereHas('sale', fn($s) => $s->where('user_id', $cashierId))
        );
    }

    /* ===========================
     |  HELPERS
     |===========================*/

    /** Hitung total baris (qty * price + pajak). tax_percent berbasis 0-100. */
    public function recalcLineTotal(): void
    {
        $qty   = (int) ($this->qty ?? 0);
        $price = (float) ($this->price ?? 0);
        $tax   = (float) ($this->tax_percent ?? 0);

        $base  = $qty * $price;
        $this->total = $base + ($tax > 0 ? $base * ($tax / 100) : 0);
    }

    /** Kalau model Sale punya method recalcTotals(), panggil untuk sync grand total. */
    public function syncSaleTotals(): void
    {
        $sale = $this->sale()->first();
        if ($sale && method_exists($sale, 'recalcTotals')) {
            $sale->recalcTotals();
        }
    }

    /* ===========================
     |  HELPERS INVENTORY
     |===========================*/

    /**
     * Tulis/replace movement OUT untuk baris sale item ini.
     * reference = "sale_item:{id}" untuk idempoten update.
     * Menyertakan user_id dari Sale (fallback user login) dan created_at = waktu Sale.
     */
    protected function writeOutMovement(): void
    {
        // Bersihkan movement lama (jika ada) untuk reference ini (skenario updated)
        InventoryMovement::where('type', 'OUT')
            ->where('reference', 'sale_item:'.$this->id)
            ->delete();

        if ((int) $this->qty > 0) {
            $sale      = $this->sale; // sudah di-relasiin
            $userId    = optional($sale)->user_id ?? (auth()->check() ? auth()->id() : null);
            $createdAt = $sale?->{$sale->getCreatedAtColumn()} ?? now();

            InventoryMovement::create([
                'product_id' => $this->product_id,
                'sale_id'    => $this->sale_id,   // <-- pastikan kolom ini ada di inventory_movements
                'user_id'    => $userId,          // <-- standarisasi: pakai user_id, bukan cashier_id
                'type'       => 'OUT',
                'qty'        => (int) $this->qty,
                'reference'  => 'sale_item:'.$this->id,
                'notes'      => $this->name,
                'created_at' => $createdAt,
                'updated_at' => now(),
            ]);
        }
    }

    /** Hapus movement OUT milik baris ini. */
    protected function removeOutMovement(): void
    {
        InventoryMovement::where('type', 'OUT')
            ->where('reference', 'sale_item:'.$this->id)
            ->delete();
    }

    /* ===========================
     |  MODEL EVENTS
     |===========================*/
    protected static function booted(): void
    {
        // Saat menyimpan: isi default dari Product & hitung total
        static::saving(function (SaleItem $item) {
            $item->qty = max(1, (int) $item->qty);

            if ($item->product_id) {
                $prod = $item->relationLoaded('product') ? $item->product : $item->product()->first();

                if ($prod) {
                    if (!$item->name)  $item->name  = $prod->name;
                    if ($item->price === null && isset($prod->sell_price))    $item->price = $prod->sell_price;
                    if ($item->tax_percent === null && isset($prod->tax_percent)) $item->tax_percent = $prod->tax_percent;
                }
            }

            $item->recalcLineTotal();
        });

        

        // Sesudah create/update/delete → sync total sale + kelola movement OUT
        static::created(function (SaleItem $item) {
            $item->syncSaleTotals();
            $item->writeOutMovement();   // buat OUT
        });

        static::updated(function (SaleItem $item) {
            $item->syncSaleTotals();

            // id tidak berubah; cukup tulis ulang OUT (hapus-ulang via reference)
            $item->writeOutMovement();
        });

        static::deleted(function (SaleItem $item) {
            $item->syncSaleTotals();
            $item->removeOutMovement();  // hapus OUT
        });
    }
}
