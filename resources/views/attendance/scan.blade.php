@extends('layouts.app', ['title' => 'Imbas Kehadiran'])
@section('content')
<div class="card"><div class="card-body"><h1 class="h4">Imbas QR Kehadiran</h1><p class="text-muted">Imbas QR menggunakan kamera atau masukkan token aktiviti.</p><div id="reader" class="mb-3 qr-reader"></div><form method="post" action="{{ route('attendance.store') }}">@csrf<div class="input-group"><input id="token" class="form-control" name="token" value="{{ request('token') }}" placeholder="Token QR" required><button class="btn btn-danger">Rekod Kehadiran</button></div></form></div></div>
@push('scripts')<script src="https://unpkg.com/html5-qrcode"></script><script>if (window.Html5QrcodeScanner) { const scanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 }); scanner.render((decodedText) => { document.getElementById('token').value = decodedText.split('token=')[1] || decodedText; }); }</script>@endpush
@endsection
