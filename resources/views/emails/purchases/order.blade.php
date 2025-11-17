@component('mail::message')
# Purchase Order #{{ $purchase->id }}

Supplier: **{{ $purchase->supplier->name }}**
Tanggal PO: **{{ $purchase->tanggal?->format('d M Y') }}**
Status: **{{ strtoupper($purchase->status) }}**

@if($note)
> {{ $note }}
@endif

@component('mail::table')
| Produk | Qty | Harga | Diskon | Subtotal |
|:------ |:---:| -----:| ------:| -------:|
@php $grand=0; @endphp
@foreach($purchase->items as $it)
@php
$sub = max(0, ($it->qty * $it->cost) - ($it->discount ?? 0));
$grand += $sub;
@endphp
| {{ $it->product->name ?? 'Produk' }} | {{ $it->qty }} | Rp {{ number_format($it->cost,2,',','.') }} | Rp {{ number_format($it->discount ?? 0,2,',','.') }} | Rp {{ number_format($sub,2,',','.') }} |
@endforeach
@endcomponent

**Total**: Rp {{ number_format($grand,2,',','.') }}

Terima kasih.

@endcomponent
