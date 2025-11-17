@extends('layouts.app')
@section('title','Detail Supplier')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Supplier</a></li>
  <li class="breadcrumb-item active">{{ $supplier->name }}</li>
@endsection

@section('content')
<style>
:root { --maroon:#800000; --maroon-dark:#5a0000; --maroon-100:#f4e6e6; }
.card { border-radius:18px }
.text-maroon{ color:var(--maroon)!important }
.badge-maroon{ background:var(--maroon-100); color:var(--maroon); border:1px solid var(--maroon) }
.btn-maroon{ background:var(--maroon); color:#fff; border-radius:12px }
.btn-maroon:hover{ background:var(--maroon-dark); color:#fff }
.btn-outline-maroon{ background:#fff; color:var(--maroon); border:1px solid var(--maroon); border-radius:12px }
.btn-outline-maroon:hover{ background:var(--maroon-100) }
.kpi{ border:1px solid var(--maroon-100); border-radius:14px; padding:16px; background:#fff }
.kpi .h6{ color:#6b7280; margin-bottom:6px }
.kpi .value{ font-size:1.25rem; font-weight:700; color:var(--maroon) }
.table thead th{ background:var(--maroon); color:#fff; white-space:nowrap }
th,td{ vertical-align: middle }
.badge-soft-danger{ background:#fff1f2; color:#b91c1c; border:1px solid #fecaca; border-radius:12px; padding:.25rem .5rem }
</style>

<div class="container-fluid px-2 px-lg-3">

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h3 class="mb-1 text-maroon">{{ $supplier->name }}</h3>
      <div class="text-muted">
        {{ $supplier->code ?? '—' }} • {{ $supplier->city ?? '—' }}
        @if($supplier->is_active)
          <span class="ms-2 badge bg-success">Aktif</span>
        @else
          <span class="ms-2 badge bg-secondary">Nonaktif</span>
        @endif
        @if(!$supplier->email)
          <span class="ms-2 badge-soft-danger">Email belum diisi</span>
        @endif
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">← Kembali</a>
      <a href="{{ route('purchases.create', ['supplier_id'=>$supplier->id]) }}" class="btn btn-maroon">+ Purchase</a>
      @if(Route::has('suppliers.toggle'))
      <form action="{{ route('suppliers.toggle',$supplier) }}" method="post">
        @csrf
        <button class="btn btn-outline-maroon">{{ $supplier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
      </form>
      @endif
    </div>
  </div>

  {{-- BLOK AKSI EMAIL PO --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-end gap-2">
      <div class="me-auto">
        <div class="fw-semibold mb-1">Kirim PO via Email</div>
        <div class="small text-muted">
          Email tujuan: <b>{{ $supplier->email ?: '—' }}</b>
          @if(!$supplier->email)
            <a href="{{ route('suppliers.edit', $supplier) }}" class="ms-1">isi sekarang</a>
          @endif
        </div>
      </div>

      {{-- Kirim PO terbaru --}}
      <form action="{{ route('suppliers.send_last_po', $supplier) }}" method="POST"
            onsubmit="return confirm('Kirim PO terbaru ke {{ $supplier->email ?: '—' }}?')">
        @csrf
        <button class="btn btn-outline-maroon"
                {{ $supplier->email ? '' : 'disabled' }}>
          <i data-feather="send" class="me-1"></i> Kirim PO Terbaru
        </button>
      </form>

      {{-- Kirim PO tertentu --}}
      @php $purchases = $supplier->purchases ?? collect(); @endphp
      <form id="formSendSpecific" method="POST">
        @csrf
        <div class="input-group">
          <select class="form-select" id="poSelect" {{ $purchases->isEmpty() ? 'disabled' : '' }}>
            @forelse($purchases as $po)
              <option value="{{ route('suppliers.send_po', [$supplier->id, $po->id]) }}">
                #{{ $po->id }} • {{ $po->tanggal?->format('d M Y') ?? '-' }} • Rp {{ number_format($po->total,0,',','.') }}
              </option>
            @empty
              <option value="">(Belum ada PO)</option>
            @endforelse
          </select>
          <button type="button" class="btn btn-maroon"
                  onclick="sendSpecificPo()"
                  {{ ($purchases->isEmpty() || !$supplier->email) ? 'disabled' : '' }}>
            <i data-feather="send"></i>
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- INFO CARD --}}
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 rounded border" style="border-color:var(--maroon-100)">
                <div class="small text-muted">Kontak</div>
                <div class="fw-semibold">{{ $supplier->contact_person ?: '—' }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded border" style="border-color:var(--maroon-100)">
                <div class="small text-muted">Telepon</div>
                <div class="fw-semibold">{{ $supplier->phone ?: '—' }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded border" style="border-color:var(--maroon-100)">
                <div class="small text-muted">Email</div>
                <div class="fw-semibold">{{ $supplier->email ?: '—' }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded border" style="border-color:var(--maroon-100)">
                <div class="small text-muted">NPWP</div>
                <div class="fw-semibold">{{ $supplier->npwp ?: '—' }}</div>
              </div>
            </div>
            <div class="col-12">
              <div class="p-3 rounded border" style="border-color:var(--maroon-100)">
                <div class="small text-muted">Alamat</div>
                <div class="fw-semibold">{{ $supplier->address ?: '—' }}</div>
              </div>
            </div>
          </div>
        </div>

        {{-- KPI --}}
        <div class="col-md-4">
          <div class="kpi mb-2">
            <div class="h6 mb-0">Total PO</div>
            <div class="value">{{ number_format($stats['total_po'] ?? 0, 0, ',', '.') }}</div>
          </div>
          <div class="kpi mb-2">
            <div class="h6 mb-0">Total Belanja</div>
            <div class="value">Rp {{ number_format($stats['total_belanja'] ?? 0, 0, ',', '.') }}</div>
          </div>
          <div class="kpi mb-2 d-flex justify-content-between">
            <div>
              <div class="h6 mb-0">GRN</div>
              <div class="value">{{ number_format($stats['total_grn'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="text-end">
              <div class="h6 mb-0">Retur</div>
              <div class="value">{{ number_format($stats['total_return'] ?? 0, 0, ',', '.') }}</div>
            </div>
          </div>
          <div class="kpi">
            <div class="h6 mb-0">Outstanding Qty</div>
            <div class="value">{{ number_format($stats['outstanding_qty'] ?? 0, 0, ',', '.') }}</div>
            <div class="small text-muted">Belum diterima dari seluruh PO aktif</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TOP PRODUK --}}
  @if(!empty($stats['top_products']) && count($stats['top_products'])>0)
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0 text-maroon">Top Produk (PO)</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead>
            <tr>
              <th style="width:5%">#</th>
              <th>Produk</th>
              <th class="text-end" style="width:12%">Qty PO</th>
              <th class="text-end" style="width:18%">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach($stats['top_products'] as $i=>$tp)
              <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $tp['name'] ?? '—' }}</td>
                <td class="text-end">{{ number_format($tp['qty'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($tp['subtotal'] ?? 0, 0, ',', '.') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  <div class="row g-3">
    {{-- PO TERBARU --}}
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0 text-maroon">Purchase Order Terbaru</h5>
            <a href="{{ route('purchases.create', ['supplier_id'=>$supplier->id]) }}" class="btn btn-sm btn-maroon">+ Purchase</a>
          </div>

          @php
            $purchases = $supplier->purchases ?? collect();
          @endphp

          @if($purchases->isEmpty())
            <div class="text-muted py-2">Belum ada PO untuk supplier ini.</div>
          @else
            <div class="table-responsive">
              <table class="table table-bordered table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th>#PO</th>
                    <th style="width:14%">Tanggal</th>
                    <th style="width:18%">Status</th>
                    <th class="text-end" style="width:14%">Qty</th>
                    <th class="text-end" style="width:18%">Total</th>
                    <th style="width:1%"></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($purchases->take(10) as $po)
                    @php
                      $qty = optional($po->items)->sum('qty') ?? 0;
                      $badge = match($po->status){
                        'draft'            => 'badge-maroon',
                        'ordered'          => 'bg-secondary',
                        'partial_received' => 'bg-warning text-dark',
                        'received'         => 'bg-success',
                        default            => 'bg-light text-dark',
                      };
                      $statusLabel = match($po->status){
                        'draft'            => 'Draft',
                        'ordered'          => 'Dipesan',
                        'partial_received' => 'Sebagian Diterima',
                        'received'         => 'Selesai',
                        default            => ucfirst($po->status ?? '—'),
                      };
                    @endphp
                    <tr>
                      <td>#{{ $po->id }}</td>
                      <td>{{ \Carbon\Carbon::parse($po->tanggal)->format('d M Y') }}</td>
                      <td><span class="badge {{ $badge }}">{{ $statusLabel }}</span></td>
                      <td class="text-end">{{ number_format($qty,0,',','.') }}</td>
                      <td class="text-end">Rp {{ number_format($po->total,0,',','.') }}</td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <a href="{{ route('purchases.show',$po) }}" class="btn btn-outline-secondary">Detail</a>
                          @if($supplier->email)
                          <form action="{{ route('suppliers.send_po', [$supplier->id, $po->id]) }}" method="POST"
                                onsubmit="return confirm('Kirim PO #{{ $po->id }} ke {{ $supplier->email }}?')">
                            @csrf
                            <button class="btn btn-outline-maroon">
                              <i data-feather="send"></i>
                            </button>
                          </form>
                          @endif
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- RETUR SUPPLIER --}}
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-2 text-maroon">Retur ke Supplier</h5>

          @php
            $returns = $supplier->returns()->with(['items.product'])->latest('tanggal')->take(10)->get();
          @endphp

          @if($returns->isEmpty())
            <div class="text-muted py-2">Belum ada retur untuk supplier ini.</div>
          @else
            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width:14%">Tanggal</th>
                    <th style="width:20%">Tipe</th>
                    <th class="text-end" style="width:18%">Klaim</th>
                    <th style="width:1%"></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($returns as $r)
                    <tr>
                      <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                      <td>
                        <span class="badge {{ $r->type==='send_back' ? 'bg-warning text-dark':'bg-danger' }}">
                          {{ strtoupper($r->type) }}
                        </span>
                      </td>
                      <td class="text-end">Rp {{ number_format($r->total_claim,0,',','.') }}</td>
                      <td class="text-center">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('supplier_returns.show', $r) }}">Detail</a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
function sendSpecificPo(){
  const sel = document.getElementById('poSelect');
  if(!sel?.value){ alert('Tidak ada PO yang bisa dikirim.'); return; }
  @if(!$supplier->email)
    alert('Supplier belum memiliki email. Silakan isi dulu.');
    return;
  @endif
  if(!confirm('Kirim PO terpilih ke email supplier?')) return;
  const f = document.getElementById('formSendSpecific');
  f.action = sel.value;
  f.submit();
}
</script>
@endpush
@endsection
