<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\GolonganObatController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ApotekerController;
use App\Http\Controllers\PabrikController;
use App\Http\Controllers\LokasiObatController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\SaleItemController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Purchasing\SupplierReportController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\Reports\PurchaseReportController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\StockObatController;
use App\Http\Controllers\Reports\PembelianReportController;
use App\Http\Controllers\SwitchAccountController;
use App\Http\Controllers\Reports\LaporanExpiredController;


/*
|--------------------------------------------------------------------------
| PUBLIC / AUTH REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('dashboard'))->middleware('auth');

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /* ================= PROFILE ================= */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Avatar {user} opsional; jika null pakai user login
    Route::get('/avatar/{user?}', [ProfileController::class, 'avatar'])
        ->whereNumber('user')
        ->name('profile.avatar');

    /* ================= PRODUCTS ================= */
    // Penting: export & helper routes diletakkan SEBELUM resource agar tidak ketangkap {product}
    Route::get('products/export/pdf',  [ProductController::class, 'exportPdf'])->name('products.export.pdf');
    Route::get('products/export/xlsx', [ProductController::class, 'exportXlsx'])->name('products.export.xlsx');
    Route::get('products/export/xls',  [ProductController::class, 'exportXls'])->name('products.export.xls');

    Route::get('products/lookup',      [ProductController::class, 'lookup'])->name('products.lookup');
    Route::post('products/quick-store',[ProductController::class, 'quickStore'])->name('products.quickStore');

    Route::resource('products', ProductController::class)
        ->names('products')
        ->parameters(['products' => 'product'])
        ->whereNumber('product'); // cegah "export" terbaca sebagai {product}

    /* ================= SUPPLIERS ================= */
    Route::get('suppliers/lookup',     [SupplierController::class, 'lookup'])->name('suppliers.lookup');
    Route::get('suppliers/export/csv', [SupplierController::class, 'exportCsv'])->name('suppliers.export.csv');

    Route::resource('suppliers', SupplierController::class)
        ->names('suppliers')
        ->whereNumber('supplier');

    Route::get('suppliers/{supplier}/create-po', [SupplierController::class, 'createPurchase'])
        ->whereNumber('supplier')->name('suppliers.create_po');

    Route::post('suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])
        ->whereNumber('supplier')->name('suppliers.toggle');

    Route::get('suppliers/{supplier}/analytics', [SupplierController::class, 'analytics'])
        ->whereNumber('supplier')->name('suppliers.analytics');


    /* ================= MASTER LAIN ================= */
    if (class_exists(PabrikController::class)) {
        Route::resource('pabrik', PabrikController::class)
            ->names('pabrik');
            // ->whereNumber('pabrik'); // aktifkan jika ID numeric
    }

        /* ================= SWITCH ACCOUNT ================= */
    Route::post('/switch-account', [SwitchAccountController::class, 'switch'])
        ->name('switch.account');

    Route::resource('golongan-obat', GolonganObatController::class)
        ->names('golongan-obat')
        ->parameters(['golongan-obat' => 'golonganObat']);
        // biasanya bukan numeric (slug), jadi tidak diberi whereNumber

    Route::resource('lokasi-obat', LokasiObatController::class)
        ->names('lokasi-obat')
        ->parameters(['lokasi-obat' => 'lokasiObat']);
        // biasanya bukan numeric (slug)

    Route::resource('apoteker', ApotekerController::class)
        ->names('apoteker');
        // ->whereNumber('apoteker'); // aktifkan jika ID numeric

    /* ================= PENJUALAN (POS) ================= */
    Route::resource('sale-items', SaleItemController::class)
        ->parameters(['sale-items' => 'saleItem']);
        // ->whereNumber('saleItem'); // aktifkan jika ID numeric

    Route::get('/kasir/struk/{sale}', [SaleItemController::class, 'printReceipt'])
        ->whereNumber('sale')->name('kasir.struk');

    Route::post('/kasir/checkout', [SaleItemController::class, 'checkout'])->name('kasir.checkout');

    /* ================= PURCHASES (PO) ================= */
    Route::get('purchases/products-lookup', [PurchaseController::class, 'productsLookup'])
        ->name('purchases.productsLookup');

    Route::resource('purchases', PurchaseController::class)
        ->names('purchases')
        ->whereNumber('purchase');

    Route::post('purchases/{purchase}/submit', [PurchaseController::class, 'submit'])
        ->whereNumber('purchase')->name('purchases.submit');

    Route::get('purchases/{purchase}/print/blanko', [PurchaseController::class, 'printBlanko'])
        ->whereNumber('purchase')->name('purchases.print.blanko');

    /* ================= GRN (PENERIMAAN BARANG) ================= */
    Route::prefix('penerimaan-barang')->group(function () {
        Route::get('/',                  [GoodsReceiptController::class, 'index'])->name('grn.index');
        Route::get('/{grn}',             [GoodsReceiptController::class, 'show'])->whereNumber('grn')->name('grn.show');
        Route::get('/create/{purchase}', [GoodsReceiptController::class, 'create'])->whereNumber('purchase')->name('grn.create');
        Route::post('/{purchase}',       [GoodsReceiptController::class, 'store'])->whereNumber('purchase')->name('grn.store');
    });

    /* ===== Alias lama GRN (kompatibilitas) ===== */
    Route::get('goods-receipts',                   [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('goods-receipts/{grn}',             [GoodsReceiptController::class, 'show'])->whereNumber('grn')->name('goods-receipts.show');
    Route::get('goods-receipts/create/{purchase}', [GoodsReceiptController::class, 'create'])->whereNumber('purchase')->name('goods-receipts.create');
    Route::post('goods-receipts/{purchase}',       [GoodsReceiptController::class, 'store'])->whereNumber('purchase')->name('goods-receipts.store');

    Route::get('goods-receipt',                   [GoodsReceiptController::class, 'index'])->name('goods-receipt.index');
    Route::get('goods-receipt/{grn}',             [GoodsReceiptController::class, 'show'])->whereNumber('grn')->name('goods-receipt.show');
    Route::get('goods-receipt/create/{purchase}', [GoodsReceiptController::class, 'create'])->whereNumber('purchase')->name('goods-receipt.create');
    Route::post('goods-receipt/{purchase}',       [GoodsReceiptController::class, 'store'])->whereNumber('purchase')->name('goods-receipt.store');

    /* ================= PEMBELIAN =================
       /pembelian dan /pembelian/create sama-sama render create()
       (tanpa redirect) agar tidak loop.
    */
    Route::prefix('pembelian')->name('pembelian.')->group(function () {
        Route::get('/',        [PembelianController::class, 'create'])->name('index');   // GET /pembelian
        Route::get('/create',  [PembelianController::class, 'create'])->name('create');  // GET /pembelian/create
        Route::post('/',       [PembelianController::class, 'store'])->name('store');    // POST /pembelian

        // Lookup
        Route::get('/po/search',       [PembelianController::class, 'searchPO'])->name('po.search');
        Route::get('/po/{poNo}',       [PembelianController::class, 'getPO'])->name('po.get');
        Route::get('/products/search', [PembelianController::class, 'searchProducts'])->name('products.search');

        // Alias lama (opsional)
        Route::get('/search-po',       [PembelianController::class, 'searchPO'])->name('search-po');
        Route::get('/get-po/{poNo}',   [PembelianController::class, 'getPO'])->name('get-po');
        Route::get('/search-products', [PembelianController::class, 'searchProducts'])->name('search-products');
    });

    /* ================= LAPORAN PENJUALAN ================= */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales',               [SalesReportController::class, 'index'])->name('sales.index');
        Route::get('sales/items',         [SalesReportController::class, 'items'])->name('sales.items');
        Route::get('sales/{sale}',        [SalesReportController::class, 'show'])->whereNumber('sale')->name('sales.show');
        Route::get('sales-export',        [SalesReportController::class, 'exportSalesCsv'])->name('sales.export');
        Route::get('sales-items-export',  [SalesReportController::class, 'exportItemsCsv'])->name('sales.items_export');

        // PDF export
        Route::get('sales-export/pdf',       [SalesReportController::class, 'exportSalesPdf'])->name('sales.export.pdf');
        Route::get('sales-items-export/pdf', [SalesReportController::class, 'exportItemsPdf'])->name('sales.items_export.pdf');
    });

    /* ================= LAPORAN SUPPLIER ================= */
    Route::get('/purchasing/suppliers-report', [SupplierReportController::class, 'index'])
         ->name('purchasing.suppliers.report');

    /* ================= LAPORAN STOCK OBAT ================= */
    // PENTING: Export routes harus DI ATAS resource routes untuk menghindari conflict
    Route::get('/stockobat/export-pdf', [StockObatController::class, 'exportPdf'])->name('stockobat.exportPdf');
    Route::get('/stockobat/export-excel', [StockObatController::class, 'exportExcel'])->name('stockobat.exportExcel');

    /* ================= LAPORAN STOCK OBAT ================= */
    Route::get('/stockobat', [StockObatController::class, 'index'])->name('stockobat.index');
    Route::get('/stockobat/{id}', [StockObatController::class, 'show'])->name('stockobat.show');
    Route::get('/stockobat/create', [StockObatController::class, 'create'])->name('stockobat.create');
    Route::post('/stockobat', [StockObatController::class, 'store'])->name('stockobat.store');
    Route::get('/stockobat/{id}/edit', [StockObatController::class, 'edit'])->name('stockobat.edit');
    Route::put('/stockobat/{id}', [StockObatController::class, 'update'])->name('stockobat.update');


    /* ================= LAPORAN PURCHASE ORDER ================= */
    Route::prefix('reports/purchases')->name('reports.purchases.')->group(function () {
        // Index - Halaman utama laporan PO dengan filter
        Route::get('/', [PurchaseReportController::class, 'index'])
            ->name('index');

        // Show - Detail laporan per Purchase Order
            Route::get('/{purchase}', [PurchaseReportController::class, 'show'])
                ->whereNumber('purchase')
                ->name('show');

            // Export ke PDF
            Route::get('/export/pdf', [PurchaseReportController::class, 'exportPdf'])
                ->name('export.pdf');

            // Export ke Excel
            Route::get('/export/excel', [PurchaseReportController::class, 'exportExcel'])
                ->name('export.excel');

            // API untuk statistik (optional - untuk dashboard/chart)
            Route::get('/api/statistics', [PurchaseReportController::class, 'statistics'])
                ->name('statistics');

            // Laporan detail items
            Route::get('/items/report', [PurchaseReportController::class, 'itemsReport'])
                ->name('items');
        });

    /* ================= LAPORAN PEMBELIAN ================= */
    Route::prefix('reports/pembelian')->name('reports.pembelian.')->group(function () {
        // Index - Halaman utama laporan pembelian dengan filter
        Route::get('/', [PembelianReportController::class, 'index'])
            ->name('index');

        // Show - Detail laporan per Pembelian
        Route::get('/{pembelian}', [PembelianReportController::class, 'show'])
            ->whereNumber('pembelian')
            ->name('show');

        // Export ke PDF
          Route::get('/export/pdf', [PembelianReportController::class, 'exportPdf'])->name('export.pdf');

        // Export ke Excel
        Route::get('/export/excel', [PembelianReportController::class, 'exportExcel'])->name('export.excel');

        // API untuk statistik (optional - untuk dashboard/chart)
        Route::get('/api/statistics', [PembelianReportController::class, 'statistics'])
            ->name('statistics');

        // Laporan detail items
        Route::get('/items/report', [PembelianReportController::class, 'itemsReport'])
            ->name('items');

        // Laporan hutang/payable
        Route::get('/hutang/report', [PembelianReportController::class, 'hutangReport'])
            ->name('hutang');
    });

    Route::prefix('reports/expired')->name('reports.expired.')->group(function () {
        Route::get('/', [LaporanExpiredController::class, 'index'])->name('index');
        Route::get('/export', [LaporanExpiredController::class, 'export'])->name('export');
    });

    // API untuk notifikasi (opsional)
    Route::get('/api/upcoming-expired', [LaporanExpiredController::class, 'getUpcomingExpired'])->name('api.upcoming-expired');


});

require __DIR__ . '/auth.php';
