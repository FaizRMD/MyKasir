<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sale_id',      // <-- tambahkan biar bisa mass-assignment
        'type',
        'qty',
        'reference',
        'notes',
        'batch_no',
        'expiry_date'
    ];

    protected $casts = [
        'qty' => 'integer',
        'expiry_date' => 'date',
    ];

    /** Relasi ke Product */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Relasi ke Sale (khusus untuk OUT) */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /** Scope helper untuk IN/OUT */
    public function scopeIn($q)
    {
        return $q->where('type', 'IN');
    }

    public function scopeOut($q)
    {
        return $q->where('type', 'OUT');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
