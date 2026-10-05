@extends('layouts.app', ['title' => 'Imbas Kehadiran'])
@section('content')
<div class="card">
    <div class="card-body">
        <h1 class="h4">Imbas QR Kehadiran</h1>
        <p class="text-muted">Imbas kod QR menggunakan kamera atau masukkan token aktiviti.</p>
        <div id="reader" class="mt-3 qr-reader"></div>
        <form method="post" action="{{ route('attendance.store') }}" class="mt-3">
            @csrf
            <label class="form-label" for="token">Token QR</label>
            <div class="input-group">
                <input id="token" class="form-control" name="token" value="{{ request('token') }}" required>
                <button type="submit" class="btn btn-danger">Rekod Kehadiran</button>
            </div>
            @include('partials.errors', ['name' => 'token'])
        </form>
    </div>
</div>
@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    if (window.Html5QrcodeScanner) {
        const scanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 });
        scanner.render((decodedText) => {
            let token = decodedText;
            try {
                token = new URL(decodedText, window.location.href).searchParams.get('token') || decodedText;
            } catch (error) {
                token = decodedText;
            }
            document.getElementById('token').value = token;
        });
    }
</script>
@endpush
@endsection
