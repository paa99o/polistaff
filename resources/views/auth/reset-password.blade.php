@extends('layouts.app', ['title' => 'Tetapkan Kata Laluan Baharu'])

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
                        <h2 class="mb-3">Lindungi akaun anda.</h2>
                        <p class="mb-0">Gunakan kata laluan baharu yang sukar diteka dan tidak digunakan pada akaun lain.</p>
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
                    <span class="badge mb-3"><i class="bi bi-key" aria-hidden="true"></i> Kata Laluan Baharu</span>
                    <h1>Tetapkan semula kata laluan</h1>
                    <p>Masukkan kata laluan baharu untuk akaun anda.</p>

                    <form method="post" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label class="form-label" for="email">Alamat emel</label>
                            <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus>
                            @include('partials.errors', ['name' => 'email'])
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Kata laluan baharu</label>
                            <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                            @include('partials.errors', ['name' => 'password'])
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Sahkan kata laluan baharu</label>
                            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            Simpan Kata Laluan <i class="bi bi-check2 ms-2" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
