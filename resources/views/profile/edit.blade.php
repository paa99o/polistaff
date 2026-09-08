@extends('layouts.app', ['title' => 'Edit Profil'])
@section('content')
@php
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($name) => mb_strtoupper(mb_substr($name, 0, 1)))->implode('');
@endphp

<div class="card profile-edit-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="stat-label mb-1">Profile Setup</p>
                <h1 class="h4 mb-0">Edit Profil</h1>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('profile.show') }}">Kembali</a>
        </div>

        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('put')

            <div class="profile-edit-layout">
                <aside class="profile-photo-upload">
                    <div class="profile-photo-frame profile-photo-frame-large">
                        @if($user->profile_photo_path)
                            <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="Gambar profil {{ $user->name }}">
                        @else
                            <span>{{ $initials ?: 'PS' }}</span>
                        @endif
                    </div>
                    <label class="form-label" for="profile_photo">Gambar profil</label>
                    <input class="form-control @error('profile_photo') is-invalid @enderror" id="profile_photo" type="file" name="profile_photo" accept="image/png,image/jpeg">
                    @include('partials.errors', ['name' => 'profile_photo'])
                    <div class="form-text">Format JPG atau PNG. Maksimum 2MB.</div>
                </aside>

                <div class="profile-form-fields">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama</label>
                            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @include('partials.errors', ['name' => 'name'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ic_number">IC</label>
                            <input class="form-control @error('ic_number') is-invalid @enderror" id="ic_number" name="ic_number" value="{{ old('ic_number', $user->ic_number) }}" required>
                            @include('partials.errors', ['name' => 'ic_number'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Emel</label>
                            <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @include('partials.errors', ['name' => 'email'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="department">Jabatan</label>
                            <input class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department', $user->department) }}" required>
                            @include('partials.errors', ['name' => 'department'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Telefon</label>
                            <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                            @include('partials.errors', ['name' => 'phone'])
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address">Alamat</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="4" required>{{ old('address', $user->address) }}</textarea>
                            @include('partials.errors', ['name' => 'address'])
                        </div>
                    </div>

                    <button class="btn btn-danger mt-4">Simpan Profil</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
