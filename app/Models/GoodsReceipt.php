<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $table = 'goods_receipts';

    protected $fillable = [
        'pembelian_id',
        'supplier_id',
        'received_at',
        'grn_no',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'date',
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    // alias lama (kalau masih ada kode yg pakai ->purchase)
    public function purchase(): BelongsTo
    {
        return $this->pembelian();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id');
    }
}
