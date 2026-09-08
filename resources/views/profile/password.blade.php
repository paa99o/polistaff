@extends('layouts.app', ['title' => 'Tukar Kata Laluan'])

@section('content')
<div class="card">
    <div class="card-body">
        <h1 class="h4 soft-panel-title">Tukar Kata Laluan</h1>
        <p class="text-muted">Gunakan kata laluan yang kukuh dan berbeza daripada kata laluan lama.</p>

        <form method="post" action="{{ route('profile.password.update') }}">
            @csrf
            @method('put')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="current_password">Kata Laluan Semasa</label>
                    <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                    @include('partials.errors', ['name' => 'current_password'])
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="password">Kata Laluan Baharu</label>
                    <input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password" required>
                    @include('partials.errors', ['name' => 'password'])
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="password_confirmation">Sahkan Kata Laluan</label>
                    <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                </div>
            </div>
            <button class="btn btn-danger mt-3">Kemas Kini Kata Laluan</button>
        </form>
    </div>
</div>
@endsection
