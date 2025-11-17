<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\OwnedByUser;

class Sale extends Model
{
    use HasFactory, OwnedByUser;

    /**
     * (Opsional, tapi eksplisit) — pastikan ke tabel 'sales'
     */
    protected $table = 'sales';

    protected $fillable = [
        'user_id',
        'invoice_no',
        'customer_id',
        'customer_name',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'paid',
        'change',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'customer_id'  => 'integer',
        'subtotal'     => 'decimal:2',
        'discount'     => 'decimal:2',
        'tax'          => 'decimal:2',
        'grand_total'  => 'decimal:2',
        'paid'         => 'decimal:2',
        'change'       => 'decimal:2',
    ];

    /**
     * Relasi: 1 penjualan memiliki banyak item.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    /**
     * Relasi: penjualan milik satu customer (nullable untuk walk-in).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi: penjualan dibuat oleh satu user/kasir.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

     public function getCustomerLabelAttribute(): string
    {
        return $this->customer->name ?? $this->customer_name ?? 'Umum';
    }
}
