@extends('layouts.app', ['title' => 'Lupa Kata Laluan'])

@section('content')
<div class="card auth-card">
    <div class="row g-0">
        <div class="col-md-5">
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
                        <h2 class="mb-3">Pulihkan akses akaun.</h2>
                        <p class="mb-0">Pautan selamat akan dihantar ke alamat emel yang didaftarkan pada akaun anda.</p>
                    </div>
                </div>
                <a class="home-link" href="{{ route('login') }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Kembali ke log masuk
                </a>
            </section>
        </div>

        <div class="col-md-7">
            <section class="auth-form-panel">
                <div>
                    <span class="badge mb-3"><i class="bi bi-envelope-lock" aria-hidden="true"></i> Pemulihan Akaun</span>
                    <h1>Lupa kata laluan?</h1>
                    <p>Masukkan alamat emel akaun anda untuk menerima pautan reset.</p>

                    <form method="post" action="{{ route('password.email') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label" for="email">Alamat emel</label>
                            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                            @include('partials.errors', ['name' => 'email'])
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            Hantar Pautan Reset <i class="bi bi-send ms-2" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        Sudah ingat kata laluan? <a class="fw-semibold" href="{{ route('login') }}">Log masuk</a>
                    </p>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
