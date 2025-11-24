<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $table = 'goods_receipt_items';

    protected $fillable = [
        'goods_receipt_id',
        'product_id',
        'pembelian_item_id',
        'qty',
        'price',
        'batch_no',
        'exp_date',
    ];

    protected $casts = [
        'qty'      => 'float',
        'price'    => 'float',
        'exp_date' => 'date:Y-m-d',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function pembelianItem(): BelongsTo
    {
        return $this->belongsTo(PembelianItem::class, 'pembelian_item_id');
    }
}
