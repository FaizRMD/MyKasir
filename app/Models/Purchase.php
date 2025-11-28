<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Purchase extends Model
{
    // =========================
    // Status PO
    // =========================
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ORDERED = 'ORDERED';
    public const STATUS_PARTIAL_RECEIVED = 'PARTIAL_RECEIVED';
    public const STATUS_RECEIVED = 'RECEIVED';

    protected $table = 'purchases';

    protected $fillable = [
        'po_no',
        'po_date',
        'supplier_id',
        'apoteker_id',
        'warehouse_id',   // <- kalau kamu pakai kolom ini
        'type',           // NON KONSINYASI | KONSINYASI
        'category',       // Reguler | Prekursor | ...
        'print_type',     // INV_A5 | INV_A4 | STRUK_58 | ...
        'note',
        'status',
        'total',
        'user_id',
    ];

    protected $casts = [
        'po_date' => 'date',
        'total' => 'decimal:2',
    ];

    // =========================
    // Events
    // =========================
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // Normalisasi status ke uppercase
            if (!empty($model->status)) {
                $model->status = strtoupper($model->status);
            }

            // Kalau di DB lama masih ada kolom 'tanggal', jaga kompatibilitas
            if (!Schema::hasColumn($model->getTable(), 'tanggal')) {
                unset($model->tanggal);
            }
        });
    }

    // =========================
    // Relationships
    // =========================

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function apoteker(): BelongsTo
    {
        return $this->belongsTo(Apoteker::class, 'apoteker_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // =========================
    // Scopes
    // =========================

    /** PO yang masih “open”: belum full diterima */
    public function scopeOpen($q)
    {
        return $q->whereIn('status', [
            self::STATUS_DRAFT,
            self::STATUS_ORDERED,
            self::STATUS_PARTIAL_RECEIVED,
        ]);
    }

    /** PO yang sudah selesai diterima */
    public function scopeClosed($q)
    {
        return $q->where('status', self::STATUS_RECEIVED);
    }

    /** Helper untuk filter yang masih punya outstanding qty */
    public function scopeHasOutstandingItems($q)
    {
        return $q->whereHas('items', function ($sub) {
            $sub->outstanding();  // scope di PurchaseItem
        });
    }

    // =========================
    // Accessors / Helpers
    // =========================

    /** Ambil po_date (fallback ke kolom lama 'tanggal' bila ada) */
    public function getPoDateAttribute($value)
    {
        if ($value) {
            return $this->asDateTime($value);
        }

        $tanggal = $this->attributes['tanggal'] ?? null;
        return $tanggal ? $this->asDateTime($tanggal) : null;
    }

    /** Set po_date + sinkron ke 'tanggal' jika kolom itu ada */
    public function setPoDateAttribute($value): void
    {
        $this->attributes['po_date'] = $value;

        if (Schema::hasColumn($this->getTable(), 'tanggal')) {
            $this->attributes['tanggal'] = $value;
        }
    }

    /** Total terformat */
    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2, ',', '.');
    }

    /** Hanya bisa diedit kalau status masih DRAFT */
    public function getIsEditableAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /** Total qty yang belum diterima untuk PO ini */
    public function getOutstandingQtyAttribute(): int
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return (int) $items->sum(function ($i) {
            $qty = (int) ($i->qty ?? 0);
            $recv = (int) ($i->qty_received ?? 0);
            return max(0, $qty - $recv);
        });
    }

    /** Hitung ulang total dari items */
    public function recalcTotals(): void
    {
        $query = $this->items();

        $connection = $this->getConnection();
        $schema = $connection->getSchemaBuilder();
        $table = $query->getModel()->getTable();

        $sum = 0;
        if (method_exists($schema, 'hasColumn') && $schema->hasColumn($table, 'subtotal')) {
            $sum = (float) $query->sum('subtotal');
        } else {
            $sum = (float) $query->get()->sum(function ($i) {
                return (float) ($i->qty ?? 0) * (float) ($i->cost ?? 0);
            });
        }

        $this->updateQuietly(['total' => $sum]);
    }

    /** Scope cepat untuk status PARTIAL / RECEIVED */
    public function scopeReceivedOrPartial($q)
    {
        return $q->whereIn('status', [
            self::STATUS_PARTIAL_RECEIVED,
            self::STATUS_RECEIVED,
        ]);
    }
}
