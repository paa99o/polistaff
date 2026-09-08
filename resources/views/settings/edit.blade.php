@extends('layouts.app', ['title' => 'Tetapan Sistem'])

@section('content')
<header class="settings-heading mb-4">
    <div>
        <span class="section-kicker">Pentadbiran</span>
        <h1>Tetapan Sistem</h1>
        <p>Urus identiti kelab, operasi kewangan, sandaran dan akses pengguna.</p>
    </div>
</header>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <span class="section-kicker">Konfigurasi Utama</span>
                <h2 class="h4 soft-panel-title">Identiti &amp; Kewangan</h2>
                <form method="post" action="{{ route('settings.update') }}" data-confirm="Simpan perubahan tetapan sistem?">
                    @csrf
                    @method('put')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="club_name">Nama Kelab</label>
                            <input class="form-control @error('club_name') is-invalid @enderror" id="club_name" name="club_name" value="{{ old('club_name', $settings['club_name']) }}" required>
                            @error('club_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="monthly_fee">Yuran Bulanan</label>
                            <input class="form-control @error('monthly_fee') is-invalid @enderror" id="monthly_fee" type="number" min="0" step="0.01" name="monthly_fee" value="{{ old('monthly_fee', $settings['monthly_fee']) }}" required>
                            @error('monthly_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="receipt_prefix">Awalan Nombor Resit</label>
                            <input class="form-control @error('receipt_prefix') is-invalid @enderror" id="receipt_prefix" name="receipt_prefix" value="{{ old('receipt_prefix', $settings['receipt_prefix']) }}" required>
                            @error('receipt_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contact_email">Emel Hubungan</label>
                            <input class="form-control @error('contact_email') is-invalid @enderror" id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" required>
                            @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="opening_balance">Baki Permulaan</label>
                            <input class="form-control @error('opening_balance') is-invalid @enderror" id="opening_balance" type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $settings['opening_balance']) }}" required>
                            @error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button class="btn btn-danger mt-3"><i class="bi bi-floppy me-1" aria-hidden="true"></i> Simpan Tetapan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <span class="section-kicker">Keselamatan Data</span>
                <h2 class="h5 soft-panel-title">Sandaran Data</h2>
                <p class="text-muted">Muat turun database dan semua fail upload pengguna dalam satu arkib ZIP. Maklumat rahsia aplikasi tidak disertakan.</p>
                <a class="btn btn-outline-danger" href="{{ route('backup.export') }}"><i class="bi bi-download me-1" aria-hidden="true"></i> Muat Turun Sandaran</a>

                <hr class="my-4">

                <h3 class="h6 fw-bold">Periksa Sandaran</h3>
                <p class="text-muted small">Semak kesahihan dan kandungan fail ZIP sebelum proses pemulihan dibuat.</p>
                <form method="post" action="{{ route('backup.inspect') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="form-label" for="backup_file">Fail sandaran ZIP</label>
                    <input class="form-control @error('backup_file') is-invalid @enderror" id="backup_file" type="file" name="backup_file" accept=".zip,application/zip" required>
                    @error('backup_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button class="btn btn-outline-primary mt-3" type="submit">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                        Periksa Fail
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4 maintenance-control-card {{ $settings['maintenance_enabled'] ? 'is-active' : '' }}">
    <div class="card-body">
        <div class="maintenance-control-heading">
            <div>
                <span class="section-kicker">Akses Sistem</span>
                <h2 class="h4 soft-panel-title mb-1">Mode Penyelenggaraan</h2>
                <p class="text-muted mb-0">Hadkan akses sementara apabila sistem sedang diperiksa atau dibaiki.</p>
            </div>
            <span class="system-state {{ $settings['maintenance_enabled'] ? 'is-offline' : 'is-online' }}">
                <span aria-hidden="true"></span>
                {{ $settings['maintenance_enabled'] ? 'Penyelenggaraan aktif' : 'Sistem beroperasi' }}
            </span>
        </div>

        @if($settings['maintenance_enabled'])
            <div class="maintenance-active-summary">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                <div>
                    <strong>Pengguna biasa tidak boleh mengakses sistem sekarang.</strong>
                    <p>{{ $settings['maintenance_message'] }}</p>
                    @if($settings['maintenance_estimated_end'])
                        <small>Anggaran tamat: {{ date('d/m/Y h:i A', strtotime($settings['maintenance_estimated_end'])) }}</small>
                    @endif
                </div>
            </div>

            <form method="post" action="{{ route('settings.maintenance.disable') }}" data-confirm="Matikan mode penyelenggaraan dan buka semula sistem kepada semua pengguna?">
                @csrf
                @method('delete')
                <button class="btn btn-danger" type="submit">
                    <i class="bi bi-play-circle me-1" aria-hidden="true"></i>
                    Buka Semula Sistem
                </button>
            </form>
        @else
            <form method="post" action="{{ route('settings.maintenance.enable') }}" data-confirm="Aktifkan mode penyelenggaraan? Semua pengguna selain admin akan disekat daripada sistem.">
                @csrf
                <div class="row g-3 mt-1">
                    <div class="col-lg-8">
                        <label class="form-label" for="maintenance_message">Mesej kepada pengguna</label>
                        <textarea class="form-control @error('maintenance_message') is-invalid @enderror" id="maintenance_message" name="maintenance_message" rows="3" maxlength="500" required>{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                        @error('maintenance_message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label" for="maintenance_estimated_end">Anggaran tamat <span class="text-muted">(pilihan)</span></label>
                        <input class="form-control @error('maintenance_estimated_end') is-invalid @enderror" id="maintenance_estimated_end" type="datetime-local" name="maintenance_estimated_end" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" value="{{ old('maintenance_estimated_end', $settings['maintenance_estimated_end'] ? date('Y-m-d\TH:i', strtotime($settings['maintenance_estimated_end'])) : '') }}">
                        @error('maintenance_estimated_end')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="maintenance-action-row">
                    <p><i class="bi bi-info-circle" aria-hidden="true"></i> Admin kekal boleh masuk. Semua ahli aktif menerima notifikasi dalam aplikasi; emel dihantar mengikut pilihan notifikasi mereka.</p>
                    <button class="btn btn-outline-danger" type="submit">
                        <i class="bi bi-tools me-1" aria-hidden="true"></i>
                        Aktifkan Penyelenggaraan
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
