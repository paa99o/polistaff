@extends('layouts.public', ['title' => 'Pesanan Diterima'])

@section('content')
<section class="public-container public-success-section"><div class="public-success-card"><span class="public-success-icon"><i class="bi bi-check2" aria-hidden="true"></i></span><p class="public-eyebrow">Pesanan diterima</p><h1>Terima kasih, {{ $order->customer_name }}.</h1><p>Pesanan anda telah direkodkan dan akan disemak oleh pihak POLIBEST. Simpan nombor pesanan ini untuk rujukan.</p><strong class="public-order-number">{{ $order->order_number }}</strong><div class="public-success-actions"><a class="btn btn-primary" href="{{ route('polimart.index') }}">Teruskan Membeli</a><a class="btn btn-outline-primary" href="{{ url('/') }}">Halaman Utama</a></div></div></section>
@endsection
