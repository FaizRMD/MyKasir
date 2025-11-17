<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianItem extends Model
{
    protected $table = 'pembelian_items';

    protected $fillable = [
        'pembelian_id','product_id','qty','uom','buy_price',
        'disc_percent','disc_amount','subtotal','disc_nominal',
        'hpp','hna_ppn','batch_no','exp_date'
    ];

    protected $casts = [
        'qty'          => 'float',
        'buy_price'    => 'float',
        'disc_percent' => 'float',
        'disc_amount'  => 'float',
        'subtotal'     => 'float',
        'disc_nominal' => 'float',
        'hpp'          => 'float',
        'hna_ppn'      => 'float',
        'exp_date'     => 'date',
    ];

     public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public $timestamps = true;

    public function pembelian() { return $this->belongsTo(Pembelian::class, 'pembelian_id'); }
}
