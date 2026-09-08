@extends('layouts.app', ['title' => 'Profil'])
@section('content')
@php
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($name) => mb_strtoupper(mb_substr($name, 0, 1)))->implode('');
    $memberStatusLabel = \App\Support\PolistaffLabels::status($user->membership_status);
    $memberRoleLabel = \App\Support\PolistaffLabels::role($user->role);
@endphp

<section class="profile-page">
    @if(count($missingProfileFields) > 0)
        <div class="alert alert-warning profile-completion-alert" role="alert">
            <div>
                <strong>Lengkapkan profil anda.</strong>
                <p class="mb-0">Maklumat belum lengkap: {{ implode(', ', $missingProfileFields) }}.</p>
            </div>
            <a class="btn btn-sm btn-danger" href="{{ route('profile.edit') }}">Lengkapkan</a>
        </div>
    @endif

    <div class="profile-hero card">
        <div class="card-body">
            <div class="profile-hero-main">
                <div class="profile-photo-frame">
                    @if($user->profile_photo_path)
                        <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="Gambar profil {{ $user->name }}">
                    @else
                        <span>{{ $initials ?: 'PS' }}</span>
                    @endif
                </div>
                <div>
                    <p class="stat-label mb-1">Profil Saya</p>
                    <h1>{{ $user->name }}</h1>
                    <div class="profile-badges">
                        <span class="badge text-bg-light">{{ \App\Support\PolistaffLabels::role($user->role) }}</span>
                        <span class="badge {{ \App\Support\PolistaffLabels::statusClass($user->membership_status) }}">{{ \App\Support\PolistaffLabels::status($user->membership_status) }}</span>
                        <span class="badge text-bg-light">{{ $user->department ?? 'Jabatan belum lengkap' }}</span>
                    </div>
                </div>
            </div>
            <div class="profile-actions no-print">
                <button class="btn btn-outline-secondary" type="button" onclick="window.print()"><i class="bi bi-printer me-2" aria-hidden="true"></i>Cetak Kad</button>
                <a class="btn btn-outline-secondary" href="{{ route('preferences.edit') }}"><i class="bi bi-gear me-2" aria-hidden="true"></i>Tetapan Saya</a>
                <a class="btn btn-outline-danger" href="{{ route('profile.password') }}">Kata Laluan</a>
                <a class="btn btn-danger" href="{{ route('profile.edit') }}">Edit Profil</a>
            </div>
        </div>
    </div>

    <article class="member-card card">
        <div class="member-card-body">
            <div class="member-card-brand">
                <div class="member-card-logo">@include('partials.brand-mark')</div>
                <div>
                    <strong>POLISTAFF</strong>
                    <span>Kad Ahli Kelab Staf</span>
                </div>
                <span class="member-card-status">{{ $memberStatusLabel }}</span>
            </div>
            <div class="member-card-profile">
                <div class="member-card-photo">
                    @if($user->profile_photo_path)
                        <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="Gambar profil {{ $user->name }}">
                    @else
                        <span>{{ $initials ?: 'PS' }}</span>
                    @endif
                </div>
                <div class="member-card-details">
                    <span class="member-card-label">Nama ahli</span>
                    <h2>{{ $user->name }}</h2>
                    <div class="member-card-grid">
                        <div><span>ID Ahli</span><strong>PB-{{ str_pad((string) $user->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
                        <div><span>Jabatan</span><strong>{{ $user->department ?? 'Belum dilengkapkan' }}</strong></div>
                        <div><span>Tarikh menyertai</span><strong>{{ $user->joined_date?->format('d/m/Y') ?? 'Belum dilengkapkan' }}</strong></div>
                        <div><span>Peranan</span><strong>{{ $memberRoleLabel }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <article class="activity-passport card">
        <div class="card-body">
            <div class="activity-passport-heading">
                <div>
                    <p class="stat-label mb-1">Rekod penyertaan</p>
                    <h2 class="h4 mb-0">Activity Passport</h2>
                    <p class="text-muted mb-0 mt-1">Kumpul pengalaman melalui kehadiran aktiviti Polistaff.</p>
                </div>
                <span class="activity-passport-total"><strong>{{ $attendedCount }}</strong><span>aktiviti hadir</span></span>
            </div>

            @if($nextBadge)
                <div class="activity-passport-progress">
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span>Progress ke badge <strong>{{ $nextBadge['name'] }}</strong></span>
                        <strong>{{ $attendedCount }}/{{ $nextBadge['threshold'] }}</strong>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Progress badge {{ $nextBadge['name'] }}" aria-valuenow="{{ $badgeProgress }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ $badgeProgress }}%"></div>
                    </div>
                </div>
            @else
                <div class="activity-passport-complete"><i class="bi bi-trophy-fill" aria-hidden="true"></i><span>Semua badge utama telah dicapai. Teruskan penyertaan anda.</span></div>
            @endif

            <div class="activity-badge-list">
                @forelse($earnedBadges as $badge)
                    <div class="activity-badge is-earned"><i class="bi {{ $badge['icon'] }}" aria-hidden="true"></i><span>{{ $badge['name'] }}</span><small>Dicapai</small></div>
                @empty
                    <div class="activity-badge is-locked"><i class="bi bi-lock" aria-hidden="true"></i><span>Badge pertama</span><small>Hadir 1 aktiviti</small></div>
                @endforelse
            </div>

            @if($attendances->isNotEmpty())
                <div class="activity-passport-history">
                    <div class="activity-passport-history-heading"><strong>Aktiviti terkini</strong><span>{{ $attendances->count() }} rekod scan</span></div>
                    @foreach($attendances->take(3) as $attendance)
                        @if($attendance->activity)
                            <a href="{{ route('activities.show', $attendance->activity) }}" class="activity-passport-history-item">
                                <span class="activity-history-icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                                <span><strong>{{ $attendance->activity->title }}</strong><small>{{ $attendance->scanned_at?->format('d/m/Y h:i A') }} · {{ $attendance->activity->location }}</small></span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </article>

    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <article class="card h-100">
                <div class="card-body">
                    <h2 class="h5 soft-panel-title">Maklumat Peribadi</h2>
                    <div class="profile-info-grid">
                        <div><span>Nama</span><strong>{{ $user->name }}</strong></div>
                        <div><span>Emel</span><strong>{{ $user->email }}</strong></div>
                        <div><span>IC</span><strong>{{ $user->ic_number ?? '-' }}</strong></div>
                        <div><span>Jabatan</span><strong>{{ $user->department ?? '-' }}</strong></div>
                        <div><span>Telefon</span><strong>{{ $user->phone ?? '-' }}</strong></div>
                        <div><span>Peranan</span><strong>{{ \App\Support\PolistaffLabels::role($user->role) }}</strong></div>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-lg-4">
            <article class="card h-100">
                <div class="card-body">
                    <h2 class="h5 soft-panel-title">Alamat</h2>
                    <p class="profile-address">{{ $user->address ?? 'Alamat belum dilengkapkan.' }}</p>
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
