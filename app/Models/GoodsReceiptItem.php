<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    protected $table = 'goods_receipt_items';
    protected $fillable = [
        'goods_receipt_id','product_id','purchase_item_id',
        'qty','price','batch_no','exp_date',
    ];
    protected $casts = [
        'qty'=>'int','price'=>'float','exp_date'=>'date:Y-m-d',
    ];

    public function goodsReceipt(){ return $this->belongsTo(GoodsReceipt::class,'goods_receipt_id'); }
    public function product(){ return $this->belongsTo(Product::class,'product_id'); }
    public function purchaseItem(){ return $this->belongsTo(PurchaseItem::class,'purchase_item_id'); }
}
