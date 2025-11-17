@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Tambah Golongan Obat</h1>

  <form method="POST" action="{{ route('golongan-obat.store') }}">
    @csrf
    @include('golongan._form', ['submitText' => 'Simpan'])
  </form>
</div>
@endsection
