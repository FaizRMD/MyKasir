<?php

// app/Models/Payable.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    protected $fillable = [
        'supplier_id','purchase_id',
        'issue_date','due_date',
        'amount','paid_amount','status',
        'ref_no','note',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'due_date'    => 'date',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier()  { return $this->belongsTo(Supplier::class); }
    public function purchase()  { return $this->belongsTo(Purchase::class); }

    public function getBalanceAttribute()
    {
        return max(0, (float)$this->amount - (float)$this->paid_amount);
    }
}
