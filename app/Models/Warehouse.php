<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = ['code','name','address','is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public $timestamps = true;

    public function pembelian() { return $this->hasMany(Pembelian::class, 'warehouse_id'); }

    // Utility scopes (opsional)
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function getLabelAttribute(): string
    {
        return trim(($this->code ? "{$this->code} - " : '') . ($this->name ?? ''));
    }
}
