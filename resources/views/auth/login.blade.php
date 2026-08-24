@extends('layouts.app', ['title' => 'Log Masuk'])

@section('content')
<div class="card auth-card">
    <div class="row g-0">
        <div class="col-md-6">
            <section class="auth-brand-panel">
                <div>
                    <a class="d-inline-flex align-items-center gap-3 text-dark" href="{{ url('/') }}">
                        @include('partials.brand-mark')
                        <span>
                            <span class="brand-name">POLISTAFF</span>
                            <span class="brand-description">Portal Pengurusan Kelab Staf</span>
                        </span>
                    </a>
                    <div class="auth-stripe mt-4" aria-hidden="true"><span></span><span></span><span></span></div>

                    <div class="mt-5 auth-brand-copy">
                        <h2 class="mb-3">Urus kelab staf dengan lebih teratur.</h2>
                        <p class="mb-0">Akses aktiviti, bayaran, tuntutan, kehadiran dan makluman melalui satu portal yang selamat.</p>
                    </div>

                    <ul class="auth-feature-list">
                        <li><i class="bi bi-check2" aria-hidden="true"></i><span>Rekod dan pembayaran yuran</span></li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i><span>Kehadiran aktiviti melalui QR</span></li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i><span>Notifikasi dan dokumen berpusat</span></li>
                    </ul>
                </div>

                <a class="home-link" href="{{ url('/') }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Kembali ke halaman utama
                </a>
            </section>
        </div>

        <div class="col-md-6">
            <section class="auth-form-panel">
                <div>
                    <span class="badge mb-3"><i class="bi bi-shield-check" aria-hidden="true"></i> Portal Ahli</span>
                    <h1>Selamat kembali</h1>
                    <p>Masukkan maklumat akaun anda untuk meneruskan ke dashboard.</p>

                    <form method="post" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label" for="email">Alamat emel</label>
                            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                            @include('partials.errors', ['name' => 'email'])
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Kata laluan</label>
                            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                            @include('partials.errors', ['name' => 'password'])
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="remember" value="1">
                                <span class="form-check-label small">Ingat saya</span>
                            </label>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            Log Masuk <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        Belum mempunyai akaun? <a class="fw-semibold" href="{{ route('register') }}">Daftar sebagai ahli</a>
                    </p>

                    <a class="btn btn-outline-primary w-100 mt-3 d-md-none" href="{{ url('/') }}">
                        <i class="bi bi-house me-2" aria-hidden="true"></i>Halaman Utama
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
