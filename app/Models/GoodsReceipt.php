<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GoodsReceipt extends Model
{
    protected $table = 'goods_receipts';

    protected $fillable = [
        'purchase_id','supplier_id','received_at','grn_no','notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function purchase(){ return $this->belongsTo(Purchase::class,'purchase_id'); }
    public function supplier(){ return $this->belongsTo(Supplier::class,'supplier_id'); }
    public function items(){ return $this->hasMany(GoodsReceiptItem::class,'goods_receipt_id'); }

    protected static function booted()
    {
        static::creating(function (self $grn) {
            if (empty($grn->grn_no)) {
                do {
                    $candidate = 'GRN-' . now()->format('ymd') . '-' . Str::upper(Str::random(4));
                } while (self::where('grn_no',$candidate)->exists());
                $grn->grn_no = $candidate;
            }
        });
    }
}
