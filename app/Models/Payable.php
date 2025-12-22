<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payable extends Model
{
    protected $fillable = [
        'supplier_id',
        'pembelian_id',
        'issue_date',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'ref_no',
        'note',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // Relations
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    // Accessors
    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid'
            && $this->due_date
            && $this->due_date < now();
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid')->where('status', '!=', 'lunas');
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['unpaid', 'belum lunas'])
            ->whereDate('due_date', '<', now());
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['unpaid', 'belum lunas', 'pending'])
            ->orderBy('due_date', 'asc');
    }
}
