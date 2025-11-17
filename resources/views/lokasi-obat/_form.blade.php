@once
<style>
  :root{ --maroon-700:#5a142b; --maroon-600:#6a1832; --maroon:#7b1e3b; --maroon-100:#f7ecf1; }
  .maroon-form{ background:#fff; border:1px solid rgba(123,30,59,.12); border-radius:1rem;
    box-shadow:0 8px 22px rgba(123,30,59,.08); padding:1.25rem 1.25rem; }
  .maroon-form .form-label{ color:var(--maroon-700); font-weight:600; letter-spacing:.2px; }
  .maroon-form .form-control:focus{ border-color:var(--maroon); box-shadow:0 0 0 .2rem rgba(123,30,59,.15); }
  .btn-maroon{ background:var(--maroon); border-color:var(--maroon); color:#fff; font-weight:600; border-radius:.7rem; box-shadow:0 6px 16px rgba(123,30,59,.18); }
  .btn-maroon:hover{ background:var(--maroon-600); border-color:var(--maroon-600); }
  .btn-outline-maroon{ background:#fff; color:var(--maroon-700); border:1px solid var(--maroon); border-radius:.7rem; }
  .btn-outline-maroon:hover{ background:var(--maroon); color:#fff; }
  .alert-danger{ border:1px solid rgba(220,53,69,.15); box-shadow:0 6px 16px rgba(220,53,69,.1); border-radius:.8rem; }
</style>
@endonce

@if($errors->any())
  <div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<div class="maroon-form">
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Kode</label>
      <input type="text" name="code" class="form-control" maxlength="50" required
             value="{{ old('code', $lokasiObat->code ?? '') }}">
      <small class="text-muted">Maks. 50 karakter dan unik.</small>
    </div>

    <div class="col-md-5">
      <label class="form-label">Nama</label>
      <input type="text" name="name" class="form-control" required
             value="{{ old('name', $lokasiObat->name ?? '') }}">
    </div>

    <div class="col-md-4">
      <label class="form-label">Urutan (opsional)</label>
      <input type="number" name="sort_order" class="form-control" min="0" step="1"
             value="{{ old('sort_order', $lokasiObat->sort_order) }}">
      <small class="text-muted">Untuk mengatur prioritas tampilan.</small>
    </div>

    <div class="col-12">
      <label class="form-label">Keterangan</label>
      <textarea name="description" class="form-control" rows="3">{{ old('description', $lokasiObat->description ?? '') }}</textarea>
    </div>

    <div class="col-12 d-flex gap-2 pt-1">
      <button class="btn btn-maroon">{{ $submitText ?? 'Simpan' }}</button>
      <a href="{{ route('lokasi-obat.index') }}" class="btn btn-outline-maroon">Batal</a>
    </div>
  </div>
</div>
