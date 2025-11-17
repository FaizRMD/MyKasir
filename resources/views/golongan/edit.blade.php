@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Edit Golongan Obat</h1>

  <form method="POST" action="{{ route('golongan-obat.update', $golonganObat) }}">
    @csrf
    @method('PUT')
    @include('golongan._form', ['submitText' => 'Update'])
  </form>
</div>
@endsection
