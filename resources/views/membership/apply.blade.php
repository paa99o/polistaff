@extends('layouts.app', ['title' => 'Permohonan Ahli'])

@section('content')
<div class="page-intro">
    <h1>Permohonan ahli kelab staf</h1>
    <p>Lengkapkan maklumat ini jika anda berminat menjadi ahli kelab staf. Permohonan akan disemak oleh pentadbir.</p>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('membership.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama penuh</label>
                            <input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                            @include('partials.errors', ['name' => 'name'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ic_number">Nombor kad pengenalan</label>
                            <input class="form-control" id="ic_number" name="ic_number" value="{{ old('ic_number', $user->ic_number) }}" required>
                            @include('partials.errors', ['name' => 'ic_number'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="department">Jabatan</label>
                            <input class="form-control" id="department" name="department" value="{{ old('department', $user->department) }}" required>
                            @include('partials.errors', ['name' => 'department'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nombor telefon</label>
                            <input class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel" required>
                            @include('partials.errors', ['name' => 'phone'])
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address">Alamat</label>
                            <textarea class="form-control" id="address" name="address" rows="4" required>{{ old('address', $user->address) }}</textarea>
                            @include('partials.errors', ['name' => 'address'])
                        </div>
                    </div>

                    <button class="btn btn-primary mt-4" type="submit">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        Hantar Permohonan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <aside class="card">
            <div class="card-body">
                <span class="badge mb-3"><i class="bi bi-bank" aria-hidden="true"></i> Yuran Pendaftaran</span>
                <h2 class="h5 mb-3">Maklumat bayaran</h2>
                <dl class="row mb-0">
                    <dt class="col-5">Jumlah</dt>
                    <dd class="col-7">RM10.00</dd>
                    <dt class="col-5">Bank</dt>
                    <dd class="col-7">Maybank</dd>
                    <dt class="col-5">No. akaun</dt>
                    <dd class="col-7">5621 0987 3344</dd>
                    <dt class="col-5">Nama akaun</dt>
                    <dd class="col-7">Kelab Staf POLISTAFF</dd>
                </dl>
            </div>
        </aside>
    </div>
</div>
@endsection
