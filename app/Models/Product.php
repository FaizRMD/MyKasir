<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    // Lengkap (termasuk kolom pack-pricing). Nanti akan difilter di controller.
    protected $fillable = [
        'sku','name','category','unit','barcode',
        'buy_price','sell_price','tax_percent',
        'stock','min_stock',
        'is_active','is_medicine','drug_class','is_compounded',
        'supplier_id','drug_group_id','drug_location_id',
        // pack-pricing (opsional; mungkin belum ada di DB lama)
        'pack_name','pack_qty','sell_unit',
        'buy_price_pack','ppn_percent','disc_percent','disc_amount',
        'margin_amount','margin_percent',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'is_medicine'   => 'boolean',
        'is_compounded' => 'boolean',

        // harga per UNIT (selalu ada di skema lama)
        'buy_price'     => 'decimal:2',
        'sell_price'    => 'decimal:2',
        'tax_percent'   => 'decimal:2',
        'stock'         => 'integer',
        'min_stock'     => 'integer',

        // pack-pricing (mungkin belum ada di DB lama)
        'buy_price_pack'=> 'decimal:2',
        'ppn_percent'   => 'decimal:2',
        'disc_percent'  => 'decimal:2',
        'disc_amount'   => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'margin_percent'=> 'decimal:2',
        'pack_qty'      => 'integer',
    ];

    /* ===== Relasi ===== */
    public function batches(){ return $this->hasMany(ProductBatch::class); }
    public function saleItems(){ return $this->hasMany(SaleItem::class); }
    public function supplier(){ return $this->belongsTo(Supplier::class,'supplier_id')->withDefault(['name'=>'—']); }
    public function drugGroup(){ return $this->belongsTo(GolonganObat::class,'drug_group_id')->withDefault(['name'=>'—']); }
    public function drugLocation(){ return $this->belongsTo(LokasiObat::class,'drug_location_id')->withDefault(['name'=>'—']); }

    /* ===== Scopes ===== */
    public function scopeActive($q){ return $q->where('is_active', true); }
    public function scopeExpired($q){
        return $q->whereHas('batches', fn($b)=>$b->where('qty','>',0)->whereDate('expiry_date','<',now()));
    }
    public function scopeExpiringSoon($q,$days=30){
        return $q->whereHas('batches', fn($b)=>$b->where('qty','>',0)
            ->whereDate('expiry_date','>=',now())
            ->whereDate('expiry_date','<=',now()->addDays($days)));
    }
    public function scopeSearch($q, ?string $term){
        if(!$term) return $q;
        return $q->where(fn($w)=>$w->where('name','like',"%{$term}%")
            ->orWhere('sku','like',"%{$term}%")
            ->orWhere('barcode','like',"%{$term}%"));
    }

    /* ===== Utilities ===== */
    public function recalcStock(): void
    {
        $sum = (int) $this->batches()->sum('qty');
        if ($this->stock !== $sum) { $this->stock = $sum; $this->saveQuietly(); }
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) return 'empty';
        if ($this->min_stock > 0 && $this->stock <= $this->min_stock) return 'low';
        return 'ok';
    }

    public function getSupplierNameAttribute(): string
    {
        return $this->supplier?->name ?? '—';
    }

    /** Apakah tabel products punya kolom pack-pricing? */
    public static function supportsPackPricing(): bool
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        $need = ['ppn_percent','disc_percent','disc_amount','buy_price_pack','pack_qty','sell_unit','pack_name','margin_amount','margin_percent'];
        $cols = Schema::getColumnListing((new static)->getTable());
        $cache = count(array_intersect($need, $cols)) >= 3; // cukup deteksi beberapa kolom kunci
        return $cache;
    }

    /**
     * Kalkulasi:
     * - Jika pack-pricing tersedia → hitung dari box → unit (ala vmedis)
     * - Jika tidak → pakai buy_price (unit) vs sell_price langsung
     */
    public function recalcCostAndMargin(?float $sellPriceUnit = null): void
    {
        $sellUnit = $sellPriceUnit ?? (float) $this->sell_price;

        if (self::supportsPackPricing()) {
            $packPrice   = (float) $this->buy_price_pack;
            $ppnPct      = (float) $this->ppn_percent;
            $discPct     = (float) $this->disc_percent;
            $discNominal = (float) $this->disc_amount;
            $packQty     = max(1, (int) $this->pack_qty);

            $grossPack   = $packPrice * (1 + $ppnPct/100);
            $netPack     = $grossPack - ($packPrice * ($discPct/100)) - $discNominal;
            $hppUnit     = $netPack / $packQty;

            $this->buy_price  = round($hppUnit, 2);
            $this->sell_price = round($sellUnit, 2);
        } else {
            // fallback skema lama
            $hppUnit = (float) $this->buy_price;
            $this->sell_price = round($sellUnit, 2);
        }

        $marginAmt            = max(0, $this->sell_price - $this->buy_price);
        $this->margin_amount  = round($marginAmt, 2);
        $this->margin_percent = round($this->sell_price > 0 ? ($marginAmt / $this->sell_price) * 100 : 0, 2);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->orderBy('created_at', 'desc');
    }


}
