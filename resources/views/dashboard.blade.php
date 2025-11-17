@extends('layouts.app')

@section('title','Dashboard')

@push('styles')
<style>
  /* ========= THEME ========= */
  :root{
    --brand-900:#5e0d0d; --brand-700:#8d1b1b; --brand-500:#b33a3a; --brand-300:#ef9a9a;
    --ink:#0f172a; --ink-soft:#475569; --line:#e7ebf5; --bg:#f6f8fc; --panel:#ffffff; --panel-tint:#fff5f5;
    --ok:#16a34a; --warn:#f59e0b; --danger:#ef4444;
    --shadow:0 12px 30px rgba(15,23,42,.06); --radius:18px;
  }
  [data-theme="dark"]{
    --ink:#e6edf9; --ink-soft:#9fb3d9; --line:#22314b; --bg:#0b1120; --panel:#0f172a; --panel-tint:#2a1b2a;
    --shadow:0 18px 36px rgba(0,0,0,.45);
  }

  /* ========= LAYOUT ========= */
  .grid{display:grid;gap:14px}
  @media(min-width:992px){ .grid-4{grid-template-columns:repeat(4,1fr)} }
  @media(min-width:992px){ .grid-2{grid-template-columns:1.3fr .7fr} }

  /* ========= CARD ========= */
  .cardx{ background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden }
  .cardx .hd{ padding:12px 14px; border-bottom:1px dashed var(--line); font-weight:800; letter-spacing:.2px; display:flex; gap:.6rem; align-items:center; background:linear-gradient(90deg, var(--panel), var(--panel-tint)) }
  .cardx .bd{ padding:14px }

  /* ========= KPI ========= */
  .kpi{ position:relative; padding:16px 14px; border-radius:16px; border:1px solid var(--line);
        background:linear-gradient(180deg, var(--panel), var(--panel-tint)); transition:transform .2s, box-shadow .2s, border-color .2s }
  .kpi::after{ content:""; position:absolute; inset:0; border-radius:inherit; pointer-events:none; background:linear-gradient(90deg,rgba(141,27,27,.06),rgba(141,27,27,0)) }
  .kpi:hover{ transform:translateY(-2px); box-shadow:0 16px 40px rgba(141,27,27,.12); border-color:rgba(141,27,27,.25) }
  .kpi .lbl{font-size:.82rem;color:var(--ink-soft);font-weight:700}
  .kpi .val{font-weight:900;font-size:1.45rem; letter-spacing:.2px}
  .kpi .sub{color:var(--ink-soft); font-size:.8rem}

  /* ========= CHIP/PRESET ========= */
  .chip{ display:inline-flex;align-items:center;gap:.4rem; background:#fdeeee;color:#7d1a1a;
         padding:.22rem .55rem;border-radius:999px;border:1px solid #f5d2d2;font-weight:700;font-size:.82rem; cursor:pointer; user-select:none }
  .chip.active{ background:#8d1b1b; color:#fff; border-color:#8d1b1b }
  [data-theme="dark"] .chip{ background:rgba(211,90,90,.15); color:#ffdcdc; border-color:rgba(211,90,90,.35) }
  [data-theme="dark"] .chip.active{ background:#b33a3a; color:#fff; border-color:#b33a3a }

  /* ========= TABLE ========= */
  .table-modern{width:100%;border-collapse:separate;border-spacing:0}
  .table-modern thead th{ position:sticky; top:0; background:#fff5f5; color:#7d1a1a; border-bottom:1px solid var(--line); padding:10px; font-weight:800 }
  [data-theme="dark"] .table-modern thead th{ background:#2a1b2a; color:#ffdede }
  .table-modern tbody td{ padding:10px; border-bottom:1px solid var(--line) }
  .num{ text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap }

  /* ========= FILTER BAR ========= */
  .filter{display:flex;flex-wrap:wrap;gap:.6rem; align-items:center}
  .filter .field{ display:flex; flex-direction:column; gap:.35rem }
  .filter .label{ font-size:.78rem; color:var(--ink-soft); font-weight:700 }
  .filter .ctl{
    height:40px; min-width:160px; padding:.45rem .75rem; border-radius:12px; border:1px solid var(--line); background:var(--panel);
    font-weight:600; color:var(--ink);
  }
  .btn-outline-brand{ border:1px solid var(--brand-700); color:var(--brand-700); background:transparent; font-weight:800; border-radius:12px; height:40px; padding:0 .9rem }
  .btn-outline-brand:hover{ background:var(--brand-700); color:#fff }
  .filter .spacer{ flex:1 }
  .bcrumb{ display:inline-flex;align-items:center;gap:.35rem; padding:.18rem .55rem;border:1px solid var(--line); border-radius:999px;font-size:.78rem;color:var(--ink-soft); background:var(--panel) }
</style>
@endpush

@section('content')
  {{-- ====================== FILTER BAR ====================== --}}
  @php
    use Carbon\Carbon;
    $now = Carbon::now();

    // ====== nilai dari request ======
    $currentMonth = (int) request('month', $now->month);
    $currentYear  = (int) request('year',  $now->year);
    $mode         = request('mode', 'monthly'); // 'monthly' | 'range'
    $preset       = request('preset');          // '30d' | 'this-year' | 'last-year' | null
    $dateFromReq  = request('date_from');       // yyyy-mm-dd
    $dateToReq    = request('date_to');         // yyyy-mm-dd
    $selectedCashier = request('cashier_id');

    // Rentang tahun UI
    $yearStart = $now->year - 5;
    $yearEnd   = $now->year + 5;

    $monthMap = [
      1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
      7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
    ];
  @endphp

  <div class="cardx mb-3">
    <div class="hd">
      <i data-feather="filter"></i> Filter Rekap
      <span class="bcrumb">
        @if($mode==='range' && $dateFromReq && $dateToReq)
          {{ \Carbon\Carbon::parse($dateFromReq)->isoFormat('D MMM Y') }} – {{ \Carbon\Carbon::parse($dateToReq)->isoFormat('D MMM Y') }}
        @else
          {{ $monthMap[$currentMonth] ?? $currentMonth }} {{ $currentYear }}
        @endif
        @if($selectedCashier) • Kasir: {{ optional($cashiers?->firstWhere('id',$selectedCashier))->name ?? '—' }} @endif
        @if($preset) • Preset: {{ $preset }} @endif
      </span>
    </div>
    <div class="bd">
      <form method="GET" class="filter" id="filterForm">
        {{-- MODE FILTER --}}
        <div class="field" style="min-width:260px">
          <div class="label">Mode</div>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="chip mode-chip {{ $mode==='monthly' ? 'active' : '' }}" data-mode="monthly">Per Bulan</button>
            <button type="button" class="chip mode-chip {{ $mode==='range' ? 'active' : '' }}" data-mode="range">Rentang Tanggal</button>
          </div>
        </div>

        {{-- PRESET CEPAT --}}
        <div class="field" style="min-width:260px">
          <div class="label">Preset Cepat</div>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="chip preset-chip {{ $preset==='30d' ? 'active' : '' }}" data-preset="30d">30 Hari Terakhir</button>
            <button type="button" class="chip preset-chip {{ $preset==='this-year' ? 'active' : '' }}" data-preset="this-year">Tahun Ini</button>
            <button type="button" class="chip preset-chip {{ $preset==='last-year' ? 'active' : '' }}" data-preset="last-year">Tahun Lalu</button>
          </div>
        </div>

        {{-- BULAN --}}
        <div class="field monthly-only">
          <label class="label" for="month">Bulan</label>
          <select class="ctl" id="month" name="month">
            @foreach($monthMap as $m => $mName)
              <option value="{{ $m }}" {{ $currentMonth===$m ? 'selected' : '' }}>{{ $mName }}</option>
            @endforeach
          </select>
        </div>

        {{-- TAHUN --}}
        <div class="field monthly-only">
          <label class="label" for="year">Tahun</label>
          <select class="ctl" id="year" name="year">
            @for($y=$yearStart; $y<=$yearEnd; $y++)
              <option value="{{ $y }}" {{ $currentYear===$y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
          </select>
        </div>

        {{-- RENTANG TANGGAL --}}
        <div class="field range-only">
          <label class="label" for="date_from">Dari Tanggal</label>
          <input type="date" class="ctl" id="date_from" name="date_from" value="{{ $dateFromReq }}">
        </div>
        <div class="field range-only">
          <label class="label" for="date_to">Sampai Tanggal</label>
          <input type="date" class="ctl" id="date_to" name="date_to" value="{{ $dateToReq }}">
        </div>

        {{-- KASIR --}}
        <div class="field">
          <label class="label" for="cashier_id">Kasir</label>
          <select class="ctl" id="cashier_id" name="cashier_id">
            <option value="">Semua Kasir</option>
            @isset($cashiers)
              @foreach($cashiers as $c)
                <option value="{{ $c->id }}" {{ (string)$selectedCashier===(string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
              @endforeach
            @endisset
          </select>
        </div>

        <div class="spacer"></div>

        {{-- TOMBOL --}}
        <div class="d-flex gap-2">
          <button class="btn-outline-brand" type="submit">
            <i data-feather="refresh-cw" style="width:16px;height:16px"></i>&nbsp; Terapkan
          </button>
          <a href="{{ route('dashboard') }}" class="btn-outline-brand" style="opacity:.8">
            <i data-feather="x-circle" style="width:16px;height:16px"></i>&nbsp; Reset
          </a>
          <input type="hidden" name="preset" id="preset" value="{{ $preset }}">
          <input type="hidden" name="mode" id="mode" value="{{ $mode }}">
        </div>
      </form>
    </div>
  </div>

  {{-- ====================== KPI ====================== --}}
  <div class="grid grid-4">
    <div class="kpi">
      <div class="lbl">Produk Aktif / Total</div>
      <div class="val">{{ number_format($activeProducts,0,',','.') }} <span class="text-muted">/ {{ number_format($totalProducts,0,',','.') }}</span></div>
      <div class="sub">Supplier: {{ number_format($suppliersCount,0,',','.') }}</div>
    </div>
    <div class="kpi">
      <div class="lbl">Stok Total</div>
      <div class="val">{{ number_format($stockTotal,0,',','.') }}</div>
      <div class="sub">Di bawah minimum: <span class="chip">{{ number_format($stockMinItems,0,',','.') }}</span></div>
    </div>
    <div class="kpi">
      <div class="lbl">Penjualan Hari Ini</div>
      <div class="val">Rp {{ number_format($salesToday,0,',','.') }}</div>
      <div class="sub">Transaksi: {{ number_format($trxToday,0,',','.') }}</div>
    </div>
    <div class="kpi">
      <div class="lbl">Omzet Bulan Ini</div>
      <div class="val">Rp {{ number_format($salesMonth,0,',','.') }}</div>
      <div class="sub">{{ \Carbon\Carbon::now()->isoFormat('MMMM Y') }}</div>
    </div>
  </div>

  <div class="grid grid-2 mt-3">
    {{-- Grafik penjualan --}}
    <div class="cardx">
      <div class="hd"><i data-feather="trending-up"></i> Penjualan Harian</div>
      <div class="bd">
        <div id="chartSales" style="height: 320px"></div>
      </div>
    </div>

    {{-- Stok masuk / keluar + ringkas --}}
    <div class="cardx">
      <div class="hd"><i data-feather="box"></i> Pergerakan Stok</div>
      <div class="bd">
        <div id="chartStock" style="height: 260px"></div>
        <div class="mt-3">
          <div class="d-flex justify-content-between">
            <div class="fw-semibold">Masuk</div>
            <div class="num">+{{ number_format($stockIn,0,',','.') }}</div>
          </div>
          <div class="d-flex justify-content-between">
            <div class="fw-semibold">Keluar</div>
            <div class="num text-danger">-{{ number_format($stockOut,0,',','.') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Top Produk --}}
  <div class="cardx mt-3">
    <div class="hd">
      <i data-feather="award"></i> Top 10 Produk
      <span class="bcrumb ms-2">
        {{ \Carbon\Carbon::parse($dateFrom)->isoFormat('D MMM Y') }} s.d. {{ \Carbon\Carbon::parse($dateTo)->isoFormat('D MMM Y') }}
      </span>
    </div>
    <div class="bd table-responsive">
      <table class="table-modern">
        <thead>
        <tr>
          <th style="width:60px">#</th>
          <th>Nama</th>
          <th class="num">Qty</th>
          <th class="num">Omzet</th>
        </tr>
        </thead>
        <tbody>
        @forelse($topProducts as $i => $row)
          <tr>
            <td>{{ $i+1 }}</td>
            <td>
              <div class="fw-semibold">{{ $row->product?->name ?? '—' }}</div>
              <div class="small text-muted">SKU: {{ $row->product?->sku ?? '—' }}</div>
            </td>
            <td class="num">{{ number_format((float)$row->qty,0,',','.') }}</td>
            <td class="num">Rp {{ number_format((float)$row->omzet,0,',','.') }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  feather.replace();

  // ===== UI COLORS =====
  const uiInk = getComputedStyle(document.documentElement).getPropertyValue('--ink') || '#0f172a';

  // ===== DATA DARI CONTROLLER (SUDAH TERFILTER SERVER-SIDE) =====
  const labels = @json($labels);
  const series = @json($series);

  // ===== CHART: PENJUALAN HARIAN =====
  const chartSales = new ApexCharts(document.querySelector("#chartSales"), {
    chart: { type: 'area', height: 320, toolbar: { show: false }, foreColor: uiInk.trim() },
    stroke: { curve: 'smooth', width: 3 },
    dataLabels: { enabled: false },
    fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] } },
    colors: ['#8d1b1b'],
    series: [{ name: 'Omzet', data: series }],
    xaxis: { categories: labels, labels: { rotate: -45 } },
    yaxis: { labels: { formatter: (v)=> new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(v||0))) } },
    tooltip: { theme: 'light', y: { formatter: (v)=> 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(v||0))) } }
  });
  chartSales.render();

  // ===== CHART: STOK MASUK/KELUAR =====
  const chartStock = new ApexCharts(document.querySelector("#chartStock"), {
    chart: { type: 'donut', height: 260, toolbar: { show:false }, foreColor: uiInk.trim() },
    labels: ['Masuk','Keluar'],
    colors: ['#198754','#dc3545'],
    series: [{{ (int)$stockIn }}, {{ (int)$stockOut }}],
    legend: { position: 'bottom' },
    dataLabels: { enabled: true }
  });
  chartStock.render();

  // ====== LOGIKA FILTER UI ======
  const modeInput   = document.getElementById('mode');
  const presetInput = document.getElementById('preset');
  const monthSel    = document.getElementById('month');
  const yearSel     = document.getElementById('year');
  const dateFromEl  = document.getElementById('date_from');
  const dateToEl    = document.getElementById('date_to');

  function applyModeUI() {
    const mode = modeInput.value || 'monthly';
    document.querySelectorAll('.monthly-only').forEach(el => el.style.display = (mode==='monthly') ? '' : 'none');
    document.querySelectorAll('.range-only').forEach(el => el.style.display   = (mode==='range')   ? '' : 'none');
  }
  applyModeUI();

  // Mode chips
  document.querySelectorAll('.mode-chip').forEach(el=>{
    el.addEventListener('click', ()=>{
      document.querySelectorAll('.mode-chip').forEach(c=>c.classList.remove('active'));
      el.classList.add('active');
      const m = el.dataset.mode;
      modeInput.value = m;

      // Pindah mode -> kosongkan preset agar tidak ambigu
      presetInput.value = '';
      document.querySelectorAll('.preset-chip').forEach(c=>c.classList.remove('active'));
      applyModeUI();
    });
  });

  // Preset chips
  document.querySelectorAll('.preset-chip').forEach(el=>{
    el.addEventListener('click', ()=>{
      document.querySelectorAll('.preset-chip').forEach(c=>c.classList.remove('active'));
      el.classList.add('active');
      const p = el.dataset.preset;
      presetInput.value = p;

      const now  = new Date();
      const thisMonth = now.getMonth()+1;
      const thisYear  = now.getFullYear();

      // Preset => set mode & nilai yang sesuai
      if(p === '30d'){
        modeInput.value = 'range';
        // Biarkan server hitung 30 hari terakhir -> kosongkan manual date
        if (dateFromEl) dateFromEl.value = '';
        if (dateToEl)   dateToEl.value   = '';
      }else if(p === 'this-year'){
        modeInput.value = 'monthly';
        if (monthSel) monthSel.value = 1;
        if (yearSel)  yearSel.value  = thisYear;
      }else if(p === 'last-year'){
        modeInput.value = 'monthly';
        if (monthSel) monthSel.value = 1;
        if (yearSel)  yearSel.value  = thisYear - 1;
      }
      applyModeUI();
    });
  });

  // Jika user manual ubah field, matikan preset supaya tidak bentrok
  [monthSel, yearSel, dateFromEl, dateToEl].forEach(el=>{
    if(!el) return;
    el.addEventListener('change', ()=> presetInput.value = '');
  });

  // Optional: auto-apply on change
  // document.getElementById('filterForm').addEventListener('change', ()=> document.getElementById('filterForm').submit());
</script>
@endpush
