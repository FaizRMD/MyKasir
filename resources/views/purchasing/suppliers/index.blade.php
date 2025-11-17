@extends('layouts.app')
@section('title','Laporan Supplier')

@push('styles')
<style>
:root{
  --m-50:#fff5f7; --m-100:#fde6ea; --m-200:#f9ccd5; --m-300:#f1a7b5;
  --m-600:#7a1020; --m-700:#5a0c18;
  --ink:#0f172a; --muted:#6b7280; --line:#e5e7eb; --soft:#fafafb;
}
.page{max-width:1200px;margin:0 auto;padding:24px}
.header{display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:12px}
.h-title{font-size:22px;font-weight:700;color:var(--ink)}
.subtle{color:var(--muted);font-size:13px}

.card{background:#fff;border:1px solid var(--line);border-radius:14px}
.card-body{padding:16px}
.card + .card{margin-top:12px}

/* Buttons */
.btn{border-radius:12px}
.btn-maroon{background:var(--m-600);border-color:var(--m-600);color:#fff}
.btn-maroon:hover{background:var(--m-700);border-color:var(--m-700)}
.btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
.btn-ghost:hover{border-color:var(--m-200);background:var(--m-50);color:var(--m-700)}

/* Filters */
.grid{display:grid;gap:10px}
.grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.label{font-size:12px;color:var(--muted);margin-bottom:6px}
.form-control, select{border-radius:12px;border:1px solid var(--line);background:#fff}
.range-chips{display:flex;flex-wrap:wrap;gap:6px}
.chip{padding:6px 10px;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:12px;cursor:pointer}
.chip:hover{border-color:var(--m-200);color:var(--m-700)}
.chip.active{background:var(--m-50);border-color:var(--m-200);color:var(--m-700);font-weight:600}

/* Actions */
.actions{display:flex;gap:8px;flex-wrap:wrap}

/* Table */
.table-wrap{border:1px solid var(--line);border-radius:14px;overflow:hidden}
.table{width:100%;border-collapse:separate;border-spacing:0}
.table thead th{background:#fff;border-bottom:1px solid var(--line);font-weight:600;color:var(--ink);position:sticky;top:0;z-index:1}
.table th,.table td{padding:12px}
.table tbody tr{border-bottom:1px solid #f2f2f4}
.table tbody tr:hover{background:#fff}
.table tbody tr:nth-child(odd){background:var(--soft)}
.text-right{text-align:right}
tfoot th{background:#fff;border-top:2px solid var(--m-200)}

/* Empty */
.empty{padding:28px;text-align:center;color:var(--muted)}
.empty .box{display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:16px;background:var(--m-50);border:1px dashed var(--m-200);margin-bottom:10px}

/* Responsive */
@media(max-width:992px){ .grid-4{grid-template-columns:repeat(2,1fr)} }
@media(max-width:600px){ .grid-4{grid-template-columns:1fr} .header{flex-direction:column;align-items:flex-start;gap:6px} }
</style>
@endpush

@section('content')
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div>
      <div class="h-title">Laporan Supplier</div>
      <div class="subtle">Ringkasan invoice, pembelian, pembayaran, dan saldo</div>
    </div>
    <div class="actions">
      <a class="btn btn-ghost"
         href="{{ route('purchasing.suppliers.report', array_merge(request()->all(), ['export' => 'csv'])) }}">
        Export CSV
      </a>
      <a class="btn btn-maroon"
         href="{{ route('purchasing.suppliers.report', array_merge(request()->all(), ['export' => 'pdf'])) }}">
        Export PDF
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card">
    <div class="card-body">
      <form method="GET" action="{{ route('purchasing.suppliers.report') }}">
        <div class="grid grid-4">
          <div>
            <div class="label">Dari Tanggal</div>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
          </div>
          <div>
            <div class="label">Sampai Tanggal</div>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
          </div>
          <div>
            <div class="label">Supplier</div>
            <select name="supplier_ids[]" class="form-control" multiple>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected(collect(request('supplier_ids'))->contains($s->id))>{{ $s->name }}</option>
              @endforeach
            </select>
            <div class="label mt-1">(Bisa pilih lebih dari satu)</div>
          </div>
          <div>
            <div class="label">Filter Total (Min/Max)</div>
            <div class="d-flex gap-2">
              <input type="number" step="0.01" name="min_total" placeholder="Min" value="{{ request('min_total') }}" class="form-control">
              <input type="number" step="0.01" name="max_total" placeholder="Max" value="{{ request('max_total') }}" class="form-control">
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
          <div class="range-chips">
            <span class="chip" data-range="today">Hari ini</span>
            <span class="chip" data-range="7d">7 Hari</span>
            <span class="chip" data-range="30d">30 Hari</span>
            <span class="chip" data-range="this_month">Bulan ini</span>
            <span class="chip" data-range="this_year">Tahun ini</span>
          </div>
          <div class="actions">
            <a href="{{ route('purchasing.suppliers.report') }}" class="btn btn-ghost">Reset</a>
            <button class="btn btn-maroon">Terapkan Filter</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Table --}}
  <div class="card">
    <div class="card-body">
      <div class="subtle mb-2">Hasil: {{ number_format(count($summary)) }} supplier</div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Supplier</th>
              <th class="text-right">Total Invoice</th>
              <th class="text-right">Total Pembelian</th>
              <th class="text-right">Total Pembayaran</th>
              <th class="text-right">Saldo Terbuka</th>
            </tr>
          </thead>
          <tbody>
          @php($sumInv=0)
          @php($sumBuy=0)
          @php($sumPay=0)
          @php($sumOut=0)
          @forelse($summary as $row)
            @php($sumInv += (int) $row->total_invoices)
            @php($sumBuy += (float) $row->total_purchase)
            @php($sumPay += (float) $row->total_payment)
            @php($sumOut += (float) $row->outstanding)
            <tr>
              <td class="fw-semibold">{{ $row->supplier_name }}</td>
              <td class="text-right">{{ number_format($row->total_invoices) }}</td>
              <td class="text-right">Rp {{ number_format($row->total_purchase, 0, ',', '.') }}</td>
              <td class="text-right">Rp {{ number_format($row->total_payment, 0, ',', '.') }}</td>
              <td class="text-right"><strong>Rp {{ number_format($row->outstanding, 0, ',', '.') }}</strong></td>
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <div class="empty">
                  <div class="box"><i data-feather="inbox"></i></div>
                  Belum ada data untuk filter yang dipilih.
                </div>
              </td>
            </tr>
          @endforelse
          </tbody>
          <tfoot>
            <tr>
              <th>Total</th>
              <th class="text-right">{{ number_format($sumInv) }}</th>
              <th class="text-right">Rp {{ number_format($sumBuy, 0, ',', '.') }}</th>
              <th class="text-right">Rp {{ number_format($sumPay, 0, ',', '.') }}</th>
              <th class="text-right">Rp {{ number_format($sumOut, 0, ',', '.') }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  const chips = document.querySelectorAll('.chip[data-range]');
  const from  = document.querySelector('input[name="date_from"]');
  const to    = document.querySelector('input[name="date_to"]');

  const pad = v => String(v).padStart(2,'0');
  const fmt = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

  function setRange(type){
    const now = new Date();
    let a = new Date(now), b = new Date(now);

    switch(type){
      case 'today': a = b = now; break;
      case '7d': a.setDate(now.getDate()-6); break;
      case '30d': a.setDate(now.getDate()-29); break;
      case 'this_month':
        a = new Date(now.getFullYear(), now.getMonth(), 1);
        b = new Date(now.getFullYear(), now.getMonth()+1, 0);
        break;
      case 'this_year':
        a = new Date(now.getFullYear(), 0, 1);
        b = new Date(now.getFullYear(), 11, 31);
        break;
    }
    from.value = fmt(a); to.value = fmt(b);
    chips.forEach(c=>c.classList.remove('active'));
    document.querySelector(`.chip[data-range="${type}"]`)?.classList.add('active');
  }

  chips.forEach(chip => chip.addEventListener('click', () => setRange(chip.dataset.range)));
})();
</script>
@endpush
