<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code','name','contact_person','phone','email','address','city','npwp','is_active','notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =========
     * Relations
     * ========= */
    public function purchases()
    {
        // FK default: supplier_id -> id (OK)
        return $this->hasMany(Purchase::class);
    }

    // Riwayat penerimaan (GRN)
    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }


    /* ======
     * Scopes
     * ====== */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /* =============
     * Accessors
     * ============= */

    // Total belanja (sum total PO)
    public function getTotalSpendAttribute(): float
    {
        $purchases = $this->relationLoaded('purchases')
            ? $this->purchases
            : $this->purchases()->get();

        return (float) $purchases->sum('total');
    }

    // Tanggal PO terakhir (pakai kolom baru: po_date)
    public function getLastPurchaseDateAttribute(): ?Carbon
    {
        $col = 'po_date';

        if ($this->relationLoaded('purchases')) {
            // Jika relasi sudah di-load, gunakan koleksi (lebih hemat query)
            $latest = $this->purchases->sortByDesc($col)->first();
            // di model Purchase, po_date sebaiknya di-cast 'date' agar sudah Carbon
            return $latest?->$col instanceof Carbon
                ? $latest->$col
                : ($latest?->$col ? Carbon::parse($latest->$col) : null);
        }

        // Ambil langsung nilai paling baru dari DB
        $val = $this->purchases()->latest($col)->value($col);
        return $val ? Carbon::parse($val) : null;
    }

    /* ==========
     * Mutators
     * ========== */

    // Normalisasi code ke uppercase
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = $value ? strtoupper(trim($value)) : null;
    }
}
