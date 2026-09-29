@extends('layouts.app', ['title' => 'Status Permohonan Ahli'])

@section('content')
<section class="page-intro">
    <p class="stat-label mb-1">Keahlian POLIBEST</p>
    <h1>Permohonan sedang disemak</h1>
    <p>Permohonan anda telah diterima. Admin akan menyemak maklumat staf sebelum mengaktifkan akses ahli.</p>
</section>

<article class="card admin-panel membership-status-panel">
    <div class="card-body d-flex align-items-start gap-3">
        <span class="stat-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
        <div>
            <h2 class="h5">Status: Menunggu kelulusan</h2>
            <p class="text-muted mb-2">Maklumat permohonan</p>
            <dl class="row mb-0">
                <dt class="col-sm-4">Nama</dt><dd class="col-sm-8">{{ $user->name }}</dd>
                <dt class="col-sm-4">Emel</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                <dt class="col-sm-4">Jabatan</dt><dd class="col-sm-8">{{ $user->department ?: '-' }}</dd>
                <dt class="col-sm-4">Pengesahan emel</dt>
                <dd class="col-sm-8">{{ $user->hasVerifiedEmail() ? 'Disahkan' : 'Belum disahkan' }}</dd>
            </dl>
            @unless($user->hasVerifiedEmail())
                <a class="btn btn-primary mt-3" href="{{ route('verification.notice') }}">Sahkan emel</a>
            @endunless
        </div>
    </div>
</article>
@endsection
