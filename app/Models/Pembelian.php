<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    protected $table = 'pembelian';

    protected $fillable = [
        'po_no','invoice_no','invoice_date','supplier_id','warehouse_id',
        'payment_type','cashbook','due_date','gross','discount_total',
        'tax_percent','tax_amount','extra_cost','net_total','notes'
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

    public $timestamps = true;

    public function items()     { return $this->hasMany(PembelianItem::class, 'pembelian_id'); }
    public function supplier()  { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
}
