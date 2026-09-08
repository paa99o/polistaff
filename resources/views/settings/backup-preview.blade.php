@extends('layouts.app', ['title' => 'Pemeriksaan Sandaran'])

@section('content')
@php
    $manifest = $inspection['manifest'];
    $totals = is_array($manifest['totals'] ?? null) ? $manifest['totals'] : [];
    $tables = is_array($manifest['tables'] ?? null) ? $manifest['tables'] : [];
    $fileCount = is_array($manifest['files'] ?? null) ? count($manifest['files']) : 0;
@endphp

<header class="settings-heading backup-preview-heading mb-4">
    <div>
        <span class="section-kicker">Keselamatan Data</span>
        <h1>Keputusan Pemeriksaan</h1>
        <p>{{ $inspection['summary']['filename'] }}</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('settings.edit') }}">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        Kembali ke Tetapan
    </a>
</header>

<section class="backup-verdict {{ $inspection['is_valid'] ? 'is-valid' : 'is-invalid' }}" aria-live="polite">
    <i class="bi {{ $inspection['is_valid'] ? 'bi-shield-check' : 'bi-shield-exclamation' }}" aria-hidden="true"></i>
    <div>
        <h2>{{ $inspection['is_valid'] ? 'Sandaran sah dan boleh dipercayai' : 'Sandaran gagal pemeriksaan' }}</h2>
        <p>
            {{ $inspection['is_valid']
                ? 'Struktur arkib, database dan fail upload telah disahkan. Tiada data dipulihkan semasa pemeriksaan ini.'
                : 'Fail ini tidak akan digunakan untuk pemulihan sehingga semua masalah di bawah diselesaikan.' }}
        </p>
    </div>
</section>

@if($inspection['errors'])
    <section class="card mt-4 backup-message-list is-error">
        <div class="card-body">
            <h2 class="h5"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Masalah Dikesan</h2>
            <ul class="mb-0">
                @foreach($inspection['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@if($inspection['warnings'])
    <section class="card mt-4 backup-message-list is-warning">
        <div class="card-body">
            <h2 class="h5"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Perhatian</h2>
            <ul class="mb-0">
                @foreach($inspection['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

<div class="row g-3 mt-1 backup-stat-grid">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body"><span>Jadual</span><strong>{{ number_format((int) ($totals['tables'] ?? count($tables))) }}</strong></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body"><span>Rekod</span><strong>{{ number_format((int) ($totals['records'] ?? 0)) }}</strong></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body"><span>Fail Upload</span><strong>{{ number_format((int) ($totals['files'] ?? $fileCount)) }}</strong></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body"><span>Saiz Arkib</span><strong>{{ \Illuminate\Support\Number::fileSize($inspection['summary']['archive_size_bytes'], precision: 1) }}</strong></div></div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <section class="card h-100">
            <div class="card-body">
                <span class="section-kicker">Kandungan Arkib</span>
                <h2 class="h4 soft-panel-title">Semakan Integriti</h2>
                <div class="backup-check-list">
                    <div class="{{ $inspection['summary']['database_verified'] && $inspection['summary']['database_data_verified'] ? 'is-passed' : 'is-failed' }}">
                        <i class="bi {{ $inspection['summary']['database_verified'] && $inspection['summary']['database_data_verified'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}" aria-hidden="true"></i>
                        <span><strong>Database</strong><small>Data pemulihan dan SQL telah disahkan</small></span>
                    </div>
                    <div class="{{ $inspection['summary']['safe_paths'] ? 'is-passed' : 'is-failed' }}">
                        <i class="bi {{ $inspection['summary']['safe_paths'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}" aria-hidden="true"></i>
                        <span><strong>Struktur fail</strong><small>Tiada laluan berbahaya atau fail sensitif</small></span>
                    </div>
                    <div class="{{ $inspection['summary']['compatible_driver'] ? 'is-passed' : 'is-failed' }}">
                        <i class="bi {{ $inspection['summary']['compatible_driver'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}" aria-hidden="true"></i>
                        <span><strong>Keserasian database</strong><small>{{ $manifest['database_driver'] ?? 'Tidak dikenal pasti' }} dengan sistem semasa</small></span>
                    </div>
                    <div class="{{ $inspection['summary']['files_verified'] === $fileCount ? 'is-passed' : 'is-failed' }}">
                        <i class="bi {{ $inspection['summary']['files_verified'] === $fileCount ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}" aria-hidden="true"></i>
                        <span><strong>Fail upload</strong><small>{{ $inspection['summary']['files_verified'] }} daripada {{ $fileCount }} fail disahkan</small></span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="card h-100">
            <div class="card-body">
                <span class="section-kicker">Metadata</span>
                <h2 class="h4 soft-panel-title">Maklumat Sandaran</h2>
                <dl class="backup-metadata">
                    <div><dt>Aplikasi</dt><dd>{{ $manifest['application'] ?? '-' }}</dd></div>
                    <div><dt>Versi format</dt><dd>{{ $manifest['format_version'] ?? '-' }}</dd></div>
                    <div><dt>Dijana pada</dt><dd>{{ isset($manifest['generated_at']) ? date('d/m/Y h:i A', strtotime($manifest['generated_at'])) : '-' }}</dd></div>
                    <div><dt>Entri arkib</dt><dd>{{ number_format($inspection['summary']['entry_count']) }}</dd></div>
                </dl>
            </div>
        </section>
    </div>
</div>

@if($tables)
    <section class="card mt-4">
        <div class="card-body">
            <span class="section-kicker">Database</span>
            <h2 class="h4 soft-panel-title">Rekod Mengikut Jadual</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Jadual</th><th class="text-end">Bilangan Rekod</th></tr></thead>
                    <tbody>
                        @foreach($tables as $table)
                            <tr><td>{{ $table['name'] ?? '-' }}</td><td class="text-end">{{ number_format((int) ($table['records'] ?? 0)) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($inspection['is_valid'] && isset($inspection['restore_token']))
    <section class="card mt-4 backup-restore-panel">
        <div class="card-body">
            <span class="section-kicker">Zon Berisiko</span>
            <h2 class="h4 soft-panel-title">Pulihkan Sandaran Ini</h2>
            <p class="text-muted">Data database dan fail upload semasa akan digantikan. Sistem akan menghasilkan backup keselamatan secara automatik sebelum proses bermula.</p>
            <form method="post" action="{{ route('backup.restore') }}" data-confirm="Teruskan pemulihan sandaran? Data sistem semasa akan digantikan.">
                @csrf
                <input type="hidden" name="restore_token" value="{{ $inspection['restore_token'] }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <label class="form-label" for="confirmation">Taip <strong>PULIHKAN</strong> untuk mengesahkan</label>
                        <input class="form-control @error('confirmation') is-invalid @enderror" id="confirmation" name="confirmation" autocomplete="off" required>
                        @error('confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-lg-5">
                        <button class="btn btn-danger w-100" type="submit">
                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>
                            Pulihkan Sandaran
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endif
@endsection
