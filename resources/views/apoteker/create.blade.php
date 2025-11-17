@extends('layouts.app')

@section('title', 'Tambah Apoteker')

@push('styles')
<style>
:root{
  --maroon-50:#fff5f7; --maroon-100:#fde4e8; --maroon-500:#7a1020;
  --maroon-600:#5a0c18; --ink:#111827; --muted:#6b7280; --border:#e5e7eb; --soft:#f9fafb;
  --danger:#dc2626; --success:#065f46; --focus:rgba(122,16,32,.15);
}
body{background:var(--soft);color:var(--ink);}
.page{max-width:1000px;margin:0 auto;padding:24px;}
.h-title{font-weight:800;font-size:28px;color:var(--maroon-600);margin:0 0 14px;}
.sub{color:var(--muted);margin-bottom:20px}

.card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,.06);overflow:hidden;}
.card .head{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:linear-gradient(90deg,var(--maroon-50),#fff);border-bottom:1px solid var(--border);color:var(--maroon-600);font-weight:700}
.card .body{padding:18px;}

.grid{display:grid;gap:14px;grid-template-columns:repeat(12,1fr)}
.col-6{grid-column:span 6} .col-4{grid-column:span 4} .col-8{grid-column:span 8}
.col-12{grid-column:span 12}
@media(max-width:900px){.grid{grid-template-columns:1fr}.col-6,.col-4,.col-8,.col-12{grid-column:span 1}}

.label{font-size:13px;color:var(--muted);margin-bottom:6px;display:block}
.input,.select,.textarea{
  width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#fff;font-size:14px;color:#111;
}
.textarea{min-height:110px;resize:vertical}
.input:focus,.select:focus,.textarea:focus{outline:none;border-color:var(--maroon-500);box-shadow:0 0 0 4px var(--focus)}
.helper{font-size:12px;color:var(--muted);margin-top:6px}
.err{font-size:12px;color:var(--danger);margin-top:6px}

.switch{display:inline-flex;align-items:center;gap:10px;cursor:pointer;user-select:none}
.switch input{display:none}
.switch .track{width:46px;height:26px;background:#e5e7eb;border-radius:999px;position:relative;transition:.2s;border:1px solid #d1d5db}
.switch .knob{position:absolute;width:22px;height:22px;background:#fff;border-radius:50%;top:50%;left:2px;transform:translateY(-50%);box-shadow:0 1px 2px rgba(0,0,0,.15);transition:.2s}
.switch input:checked + .track{background:var(--maroon-600);border-color:var(--maroon-600)}
.switch input:checked + .track .knob{left:22px}

.actions{position:sticky;bottom:0;background:rgba(255,255,255,.8);backdrop-filter:saturate(180%) blur(6px);
  border-top:1px solid var(--border);padding:12px 16px;display:flex;justify-content:space-between;align-items:center;margin-top:16px}
.btn{padding:10px 14px;border-radius:10px;border:1px solid var(--border);font-weight:700;cursor:pointer;transition:.15s}
.btn-maroon{background:var(--maroon-600);border-color:var(--maroon-600);color:#fff}
.btn-maroon:hover{background:var(--maroon-500)}
.btn-ghost{background:transparent;color:var(--maroon-600);border-color:var(--maroon-600)}
.badge-tip{display:inline-flex;align-items:center;gap:8px;background:var(--maroon-100);color:var(--maroon-600);font-weight:700;border-radius:999px;padding:6px 10px;font-size:12px}
</style>
@endpush

@section('content')
<div class="page">
  <div class="h-title">Tambah Apoteker</div>
  <div class="sub">Lengkapi data apoteker berikut. Bidang bertanda <span style="color:var(--danger)">*</span> wajib diisi.</div>

  {{-- Notifikasi error global --}}
  @if ($errors->any())
    <div class="card" style="border-color:#fecaca">
      <div class="head" style="color:#991b1b;background:#fef2f2">Validasi Gagal</div>
      <div class="body" style="color:#7f1d1d">
        <ul style="margin:0 0 0 18px">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    </div>
    <div style="height:12px"></div>
  @endif

  <form method="POST" action="{{ route('apoteker.store') }}" autocomplete="off">
    @csrf

    <div class="card">
      <div class="head">
        <span>Data Utama</span>
        <span class="badge-tip">💊 Apoteker</span>
      </div>
      <div class="body">
        <div class="grid">
          <div class="col-6">
            <label class="label">Nama Lengkap <span style="color:var(--danger)">*</span></label>
            <input class="input" name="name" value="{{ old('name') }}" placeholder="cth: drs. Apoteker Ayu Puspita" required>
            @error('name')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="col-6">
            <label class="label">Email</label>
            <input class="input" type="email" name="email" value="{{ old('email') }}" placeholder="nama@domain.com">
            @error('email')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="col-4">
            <label class="label">No. SIPA</label>
            <input class="input" name="no_sipa" value="{{ old('no_sipa') }}" placeholder="Nomor SIPA">
            @error('no_sipa')<div class="err">{{ $message }}</div>@enderror
            <div class="helper">Surat Izin Praktik Apoteker (jika ada)</div>
          </div>

          <div class="col-4">
            <label class="label">No. STRA</label>
            <input class="input" name="no_stra" value="{{ old('no_stra') }}" placeholder="Nomor STRA">
            @error('no_stra')<div class="err">{{ $message }}</div>@enderror
            <div class="helper">Surat Tanda Registrasi Apoteker</div>
          </div>

          <div class="col-4">
            <label class="label">Telepon / WA</label>
            <input class="input" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
            @error('phone')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="label">Alamat</label>
            <textarea class="textarea" name="address" placeholder="Alamat lengkap">{{ old('address') }}</textarea>
            @error('address')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="col-6">
            <label class="label">Status</label>
            <label class="switch">
              <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
              <span class="track"><span class="knob"></span></span>
              <span>Aktif</span>
            </label>
            @error('is_active')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="col-6">
            <label class="label">Catatan</label>
            <input class="input" name="note" value="{{ old('note') }}" placeholder="Catatan internal (opsional)">
            @error('note')<div class="err">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>

    <div class="actions">
      <a href="{{ route('apoteker.index') }}" class="btn btn-ghost">← Kembali</a>
      <button type="submit" class="btn btn-maroon">✔ Simpan Apoteker</button>
    </div>
  </form>
</div>
@endsection
