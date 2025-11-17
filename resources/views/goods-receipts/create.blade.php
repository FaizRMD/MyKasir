@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
<style>
    :root{
        --maroon-900:#4a0d0d;
        --maroon-800:#5e1010;
        --maroon-700:#731414;
        --maroon-600:#8a1818;
        --maroon-500:#a31d1d;
        --maroon-100:#fdeaea;
        --gray-50:#fafafa;
        --gray-100:#f4f4f5;
        --gray-200:#e5e7eb;
        --gray-600:#52525b;
        --success:#16a34a;
        --warning:#d97706;
    }
    .grn-shell{
        background:linear-gradient(180deg, var(--maroon-100), #ffffff 40%);
        min-height:100%;
        padding-bottom:4rem;
    }
    .grn-header{
        background:var(--maroon-700);
        color:#fff;
        border-radius:14px;
        padding:16px 20px;
        display:flex;align-items:center;gap:14px;
        box-shadow:0 6px 16px rgba(115,20,20,.25);
    }
    .grn-header .badge{
        background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.25);
        font-weight:600;
    }
    .meta-card{
        border:1px solid var(--gray-200);
        border-radius:14px;
        padding:14px 16px;
        background:#fff;
    }
    .table thead th{
        background:var(--gray-100) !important;
        color:#111827;
        border-bottom:1px solid var(--gray-200) !important;
        font-weight:700;
    }
    .table tbody td{
        vertical-align:middle;
        border-color:var(--gray-200) !important;
    }
    .qty-pill{
        background:var(--gray-100);
        border:1px solid var(--gray-200);
        border-radius:10px;
        padding:2px 8px;
        font-size:.85rem;
        color:var(--gray-600);
    }
    .btn-maroon{
        background:var(--maroon-600);
        color:#fff;
        border:1px solid var(--maroon-700);
        box-shadow:0 6px 14px rgba(138,24,24,.25);
    }
    .btn-maroon:hover{ background:var(--maroon-700); color:#fff; }
    .btn-ghost{
        background:#fff;border:1px solid var(--gray-200);color:#111827;
    }
    .tag-outstanding{
        background:#fff3cd;border:1px solid #ffe69c;color:#7a5c00;border-radius:999px;padding:2px 10px;font-weight:600;font-size:.85rem;
    }
    .tag-complete{
        background:#dcfce7;border:1px solid #bbf7d0;color:#065f46;border-radius:999px;padding:2px 10px;font-weight:600;font-size:.85rem;
    }
    .mini{
        font-size:.825rem;color:var(--gray-600);
    }
    .row-actions .btn{ padding:4px 10px; font-size:.85rem; }
    .table tfoot td{ border-top:2px solid var(--gray-200) !important; }
</style>

<div class="container grn-shell py-3">
    {{-- Header --}}
    <div class="grn-header mb-3">
        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;background:rgba(255,255,255,.15);">
            <i data-feather="inbox"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2">
                <h5 class="m-0 fw-bold">Penerimaan Barang</h5>
                <span class="badge">GRN</span>
            </div>
            <div class="mini">PO #{{ $purchase->id }} • {{ $purchase->supplier->name ?? 'Tanpa Supplier' }}</div>
        </div>
        <div>
            @php
                $totalOrdered = (int) $purchase->items->sum('qty');
                $totalReceived = (int) $purchase->items->sum('qty_received');
            @endphp
            @if($totalReceived >= $totalOrdered && $totalOrdered>0)
                <span class="tag-complete">PO lengkap</span>
            @else
                <span class="tag-outstanding">Outstanding: {{ max($totalOrdered - $totalReceived,0) }}</span>
            @endif
        </div>
    </div>

    {{-- Meta --}}
    <form action="{{ route('goods-receipts.store', $purchase) }}" method="POST" id="grnForm">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="meta-card">
                    <label class="form-label fw-semibold">Tanggal Penerimaan</label>
                    <input type="date" name="received_at" class="form-control @error('received_at') is-invalid @enderror"
                           value="{{ old('received_at', now()->toDateString()) }}" required>
                    @error('received_at')<div class="invalid-feedback">{{ $message }}</div>@enderror>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="meta-card">
                    <label class="form-label fw-semibold">Catatan</label>
                    <input type="text" name="notes" class="form-control" placeholder="Opsional, misal: diterima admin gudang, kurir JNE">
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header" style="background:var(--maroon-900);color:#fff;border-top-left-radius:10px;border-top-right-radius:10px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Detail Penerimaan</span>
                    <span class="mini opacity-75">Multi-batch per produk didukung</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover m-0">
                        <thead>
                            <tr>
                                <th style="width:28%">Produk</th>
                                <th class="text-center" style="width:8%">Pesan</th>
                                <th class="text-center" style="width:8%">Sudah</th>
                                <th class="text-center" style="width:10%">Terima</th>
                                <th style="width:14%">Batch No</th>
                                <th style="width:14%">Exp Date</th>
                                <th class="text-end" style="width:12%">Harga</th>
                                <th class="text-center" style="width:6%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        @foreach ($purchase->items as $i => $item)
                            @php
                                $outstanding = max(($item->qty - $item->qty_received), 0);
                                $disabled = $outstanding <= 0 ? 'disabled' : '';
                            @endphp
                            <tr data-item-index="{{ $i }}">
                                <td>
                                    <div class="fw-semibold">{{ $item->product->name ?? 'Produk tidak ditemukan' }}</div>
                                    <div class="mini">Kode: {{ $item->product->sku ?? '—' }}</div>
                                    <input type="hidden" name="items[{{ $i }}][purchase_item_id]" value="{{ $item->id }}">
                                </td>
                                <td class="text-center"><span class="qty-pill">{{ $item->qty }}</span></td>
                                <td class="text-center"><span class="qty-pill">{{ $item->qty_received }}</span></td>
                                <td class="text-center">
                                    <input {{ $disabled }} type="number" min="0" max="{{ $outstanding }}"
                                           class="form-control text-center gr-qty"
                                           name="items[{{ $i }}][rows][0][qty]"
                                           value="{{ $outstanding }}">
                                </td>
                                <td>
                                    <input {{ $disabled }} type="text" class="form-control"
                                           name="items[{{ $i }}][rows][0][batch_no]" placeholder="Contoh: B2409A">
                                </td>
                                <td>
                                    <input {{ $disabled }} type="date" class="form-control"
                                           name="items[{{ $i }}][rows][0][exp_date]">
                                </td>
                                <td class="text-end">
                                    <input {{ $disabled }} type="number" step="0.01" class="form-control text-end"
                                           name="items[{{ $i }}][rows][0][price]" value="{{ number_format($item->cost,2,'.','') }}">
                                </td>
                                <td class="text-center row-actions">
                                    <button type="button" class="btn btn-sm btn-ghost add-row" {{ $disabled }}>
                                        <i data-feather="plus"></i>
                                    </button>
                                </td>
                            </tr>
                            {{-- container baris batch tambahan untuk item ini akan disisipkan setelah tr ini --}}
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="mini">Tip: klik + untuk menambah batch kedua/ketiga pada produk yang sama.</div>
                                        <div class="mini">Total baris: <span id="rowCount">0</span></div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center bg-white">
                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-ghost">Kembali ke PO</a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-ghost" id="btnFillOutstanding">Isi Qty Outstanding</button>
                    <button type="submit" class="btn btn-maroon">Simpan Penerimaan</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Feather icons --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.feather) feather.replace();

        // Tambah batch row per item
        document.querySelectorAll('.add-row').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.currentTarget.closest('tr');
                const idx = tr.getAttribute('data-item-index');
                const tbody = document.getElementById('itemsBody');

                // hitung existing rows untuk item ini
                const existing = tbody.querySelectorAll(`tr[data-parent="${idx}"], tr[data-item-index="${idx}"]`).length - 1;
                const rowIndex = existing; // baris tambahan dimulai dari 1

                const outMax = parseInt(tr.querySelector('.gr-qty')?.getAttribute('max') || '0', 10);

                const tpl = document.createElement('tr');
                tpl.setAttribute('data-parent', idx);
                tpl.innerHTML = `
                    <td colspan="3" class="text-end mini text-muted">Batch tambahan</td>
                    <td class="text-center">
                        <input type="number" min="0" max="${outMax}" class="form-control text-center"
                               name="items[${idx}][rows][${rowIndex}][qty]" value="0">
                    </td>
                    <td>
                        <input type="text" class="form-control" name="items[${idx}][rows][${rowIndex}][batch_no]" placeholder="Batch No">
                    </td>
                    <td>
                        <input type="date" class="form-control" name="items[${idx}][rows][${rowIndex}][exp_date]">
                    </td>
                    <td class="text-end">
                        <input type="number" step="0.01" class="form-control text-end"
                               name="items[${idx}][rows][${rowIndex}][price]" value="{{ number_format(0,2,'.','') }}">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-ghost remove-row"><i data-feather="x"></i></button>
                    </td>
                `;
                tr.insertAdjacentElement('afterend', tpl);
                if (window.feather) feather.replace();
                updateRowCount();
            });
        });

        // Hapus baris tambahan
        document.getElementById('itemsBody').addEventListener('click', (e) => {
            if (e.target.closest('.remove-row')) {
                e.target.closest('tr').remove();
                updateRowCount();
            }
        });

        // Auto fill outstanding
        document.getElementById('btnFillOutstanding')?.addEventListener('click', () => {
            document.querySelectorAll('.gr-qty').forEach(inp => {
                const max = parseInt(inp.getAttribute('max') || '0', 10);
                if (!inp.disabled) inp.value = max;
            });
        });

        // Hitung baris
        function updateRowCount(){
            const body = document.getElementById('itemsBody');
            const rows = body.querySelectorAll('tr').length;
            document.getElementById('rowCount').textContent = rows;
        }
        updateRowCount();
    });
</script>
@endsection
