@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0 ps-3">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<style>
:root{--maroon:#800000;--maroon-dark:#5a0000}
.btn-maroon{background:var(--maroon);color:#fff;border:none;border-radius:10px}
.btn-maroon:hover{background:var(--maroon-dark);color:#fff}
</style>

@php
  // pastikan variabel ada agar tidak notice di halaman create
  $supplier = $supplier ?? null;
@endphp

<div class="row g-3">
  <div class="col-md-3">
    <label class="form-label">Kode</label>
    <input type="text" name="code" value="{{ old('code', $supplier->code ?? '') }}" class="form-control" maxlength="32">
  </div>
  <div class="col-md-5">
    <label class="form-label">Nama <span class="text-danger">*</span></label>
    <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Kontak Person</label>
    <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" class="form-control" maxlength="128">
  </div>

  <div class="col-md-4">
    <label class="form-label">Telepon</label>
    <input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" class="form-control" maxlength="64">
  </div>
  <div class="col-md-4">
    <label class="form-label">Email</label>
    <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" class="form-control" maxlength="128">
  </div>
  <div class="col-md-4">
    <label class="form-label">Kota</label>
    <input type="text" name="city" value="{{ old('city', $supplier->city ?? '') }}" class="form-control" maxlength="64">
  </div>

  <div class="col-12">
    <label class="form-label">Alamat</label>
    <textarea name="address" rows="2" class="form-control" maxlength="255"
      placeholder="Contoh: Jl. Merdeka No. 45, Bandung">{{ old('address', $supplier->address ?? '') }}</textarea>
  </div>

  <div class="col-md-4">
    <label class="form-label">NPWP</label>
    <input type="text" name="npwp" value="{{ old('npwp', $supplier->npwp ?? '') }}" class="form-control" maxlength="64">
  </div>

  <div class="col-md-8">
    <label class="form-label">Catatan</label>
    <textarea name="notes" rows="2" class="form-control" maxlength="255"
      placeholder="Tambahkan keterangan penting atau deskripsi supplier">{{ old('notes', $supplier->notes ?? '') }}</textarea>
  </div>

  <div class="col-md-4">
    <label class="form-label d-block">Status Supplier</label>
    <div class="form-check form-switch mt-2">
      <input class="form-check-input" type="checkbox" name="is_active" value="1"
        {{ old('is_active', isset($supplier) ? (bool)$supplier->is_active : true) ? 'checked' : '' }}>
      <label class="form-check-label">Aktif</label>
    </div>
  </div>

  <div class="col-12 d-flex justify-content-end mt-3">
    <a href="{{ route('suppliers.index') }}" class="btn btn-light me-2">Batal</a>
    <button type="submit" class="btn btn-maroon">
      {{ isset($supplier) ? 'Simpan Perubahan' : 'Simpan Supplier' }}
    </button>
  </div>
</div>
