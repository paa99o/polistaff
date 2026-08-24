@extends('layouts.app', ['title' => 'Daftar Akaun'])

@section('content')
<div class="card auth-card">
    <div class="row g-0">
        <div class="col-md-4">
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
                        <h2 class="mb-3">Cipta akaun POLISTAFF.</h2>
                        <p class="mb-0">Daftar akaun asas dahulu. Permohonan menjadi ahli kelab staf boleh dibuat selepas log masuk.</p>
                    </div>
                </div>
                <a class="home-link" href="{{ url('/') }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Kembali ke halaman utama
                </a>
            </section>
        </div>

        <div class="col-md-8">
            <section class="auth-form-panel">
                <div>
                    <span class="badge mb-3"><i class="bi bi-person-plus" aria-hidden="true"></i> Akaun Baharu</span>
                    <h1>Daftar akaun</h1>
                    <p>Lengkapkan maklumat asas untuk mula menggunakan portal.</p>

                    <form method="post" action="{{ route('register') }}">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Nama penuh</label>
                                <input class="form-control" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
                                @include('partials.errors', ['name' => 'name'])
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Alamat emel</label>
                                <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                                @include('partials.errors', ['name' => 'email'])
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Nombor telefon</label>
                                <input class="form-control" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" required>
                                @include('partials.errors', ['name' => 'phone'])
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <label class="form-label" for="register-password">Kata laluan</label>
                                <input class="form-control" id="register-password" type="password" name="password" autocomplete="new-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Sahkan kata laluan</label>
                                <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                            </div>
                        </div>

                        <button class="btn btn-primary mt-4" type="submit">Hantar Pendaftaran</button>
                        <a class="btn btn-outline-primary mt-4 ms-2" href="{{ route('login') }}">Sudah ada akaun</a>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
