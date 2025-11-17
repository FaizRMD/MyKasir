<?php

namespace App\Models\Concerns;

trait OwnedByUser
{
    public function scopeOwnedByLoggedIn($query)
    {
        return $query->where('cashier_id', auth()->id());
    }

    public function scopeFilterByCashier($query, $cashierId = null)
    {
        return $query->when($cashierId, fn($q) => $q->where('cashier_id', $cashierId));
    }
}
