<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PembelianItem extends Model
{
    protected $table = 'pembelian_items';

    protected $fillable = [
        'pembelian_id',
        'product_id',
        'qty',
        'uom',
        'buy_price',
        'disc_percent',
        'disc_amount',
        'disc_nominal',
        'subtotal',
        'hpp',
        'hna_ppn',
        'batch_no',
        'exp_date',
        'bonus_qty',
        'bonus_uom',
        'bonus_batch_no',
        'bonus_exp_date',
    ];

    protected $casts = [
        'qty'            => 'float',
        'buy_price'      => 'float',
        'disc_percent'   => 'float',
        'disc_amount'    => 'float',
        'disc_nominal'   => 'float',
        'subtotal'       => 'float',
        'hpp'            => 'float',
        'hna_ppn'        => 'float',
        'exp_date'       => 'date',
        'bonus_qty'      => 'float',
        'bonus_exp_date' => 'date',
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->exp_date) {
            return false;
        }
        return Carbon::parse($this->exp_date)->isPast();
    }

    public function getIsNearExpiryAttribute(): bool
    {
        if (!$this->exp_date) {
            return false;
        }

        return Carbon::parse($this->exp_date)->diffInMonths(now()) <= 3 && !$this->is_expired;
    }
}
