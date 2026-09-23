@extends('layouts.app', ['title' => $activity->title])

@section('content')
@php
    $isRegistered = $registration?->status === 'registered';
    $isWaitlisted = $registration?->status === 'waitlisted';
    $registeredCount = $activity->active_registrations_count;
    $waitlistedCount = $activity->waitlisted_registrations_count;
    $capacity = $activity->max_participants ?: 'Tiada had';
    $attendedUserIds = $canManageAttendance ? $activity->attendances->pluck('user_id')->all() : [];
@endphp

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div>
                        <h1 class="h4 mb-1">{{ $activity->title }}</h1>
                        <p class="text-muted mb-0">{{ $activity->date_time->format('d/m/Y h:i A') }} &middot; {{ $activity->location }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(auth()->user()->hasRole('treasurer') && $activity->status === 'pending_approval')
                            <form method="post" action="{{ route('activities.verify', $activity) }}" data-confirm="Sahkan aktiviti ini dan hantar kepada admin?">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="treasurer_notes" value="Aktiviti disokong bendahari.">
                                <button class="btn btn-sm btn-danger">Sokong</button>
                            </form>
                        @endif
                        @if(auth()->user()->hasRole('admin') && $activity->status === 'treasurer_verified')
                            <form method="post" action="{{ route('activities.approve', $activity) }}" data-confirm="Luluskan aktiviti ini dan buka kepada ahli?">@csrf @method('patch')<button class="btn btn-sm btn-danger">Luluskan</button></form>
                            <form method="post" action="{{ route('activities.reject', $activity) }}" data-confirm="Tolak aktiviti ini?">@csrf @method('patch')<input type="hidden" name="review_notes" value="Tidak memenuhi keperluan kelulusan."><button class="btn btn-sm btn-outline-danger">Tolak</button></form>
                        @endif
                        @if(auth()->id() === $activity->created_by && $activity->status === 'pending_approval')
                            <a class="btn btn-sm btn-outline-danger" href="{{ route('activities.edit', $activity) }}">Ubah</a>
                        @endif
                        @if($activity->status === 'approved' && $activity->isFinished())
                            @php
                                $activityExportOptions = [
                                    ['label' => 'PDF', 'url' => route('reports.activities.pdf', $activity), 'icon' => 'bi-file-earmark-pdf'],
                                ];

                                if ($canManageAttendance || auth()->id() === $activity->created_by) {
                                    $activityExportOptions[] = ['label' => 'CSV Kehadiran', 'url' => route('reports.activities.attendance.csv', $activity), 'icon' => 'bi-filetype-csv'];
                                }
                            @endphp
                            @include('reports._export-menu', ['label' => 'Eksport Laporan', 'buttonClass' => 'btn-sm btn-outline-secondary', 'options' => $activityExportOptions])
                        @endif
                    </div>
                </div>
                <hr>

                @if($activity->evidencePhotos->isNotEmpty())
                    <div class="row g-2 mb-4">
                        @foreach($activity->evidencePhotos as $photo)
                            <div class="col-6 col-md-4"><img class="img-fluid rounded" src="{{ Storage::disk('public')->url($photo->path) }}" alt="Bukti aktiviti oleh {{ $photo->user->name }}"></div>
                        @endforeach
                    </div>
                @endif

                <div class="row g-3 my-3">
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Status</div><strong>{{ \App\Support\PolistaffLabels::status($activity->status) }}</strong></div></div>
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Berdaftar</div><strong id="activity-registered-count">{{ $registeredCount }} / {{ $capacity }}</strong></div></div>
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Kehadiran</div><strong id="activity-attendance-count">{{ $activity->attendances_count }}</strong></div></div>
                </div>
                @if($activity->treasurer_verified_at)
                    <div class="alert alert-info small">Disahkan bendahari pada {{ $activity->treasurer_verified_at->format('d/m/Y H:i') }}. {{ $activity->treasurer_notes }}</div>
                @endif

                <div class="small text-muted mb-3">
                    Pendaftaran: {{ $activity->registration_opens_at?->format('d/m/Y h:i A') ?? 'Bila-bila masa' }} - {{ $activity->registration_closes_at?->format('d/m/Y h:i A') ?? 'Sehingga aktiviti' }}<br>
                </div>

                @if($activity->isFinished() && $isRegistered)
                    <div class="card bg-light border-0 mb-3"><div class="card-body">
                        <h2 class="h6">Bukti aktiviti</h2>
                        <p class="small text-muted">Muat naik gambar semasa aktiviti telah selesai.</p>
                        <form method="post" action="{{ route('activities.evidence-photos', $activity) }}" enctype="multipart/form-data">
                            @csrf
                            <input class="form-control" type="file" name="photos[]" accept="image/*" multiple required>
                            @include('partials.errors', ['name' => 'photos'])
                            <button class="btn btn-sm btn-danger mt-2">Muat Naik Gambar</button>
                        </form>
                    </div></div>
                @endif

                @if($activity->registrationIsOpen())
                    @if($isRegistered)
                        <div class="alert alert-success">Anda sudah berdaftar untuk aktiviti ini.</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <form method="post" action="{{ route('activities.unregister', $activity) }}" data-confirm="Batalkan pendaftaran aktiviti ini?">
                                @csrf
                                @method('delete')
                                <button class="btn btn-outline-secondary">Batalkan Pendaftaran</button>
                            </form>
                        </div>
                    @elseif($isWaitlisted)
                        <div class="alert alert-info">Anda berada dalam senarai menunggu. Anda akan dimaklumkan jika terdapat kekosongan.</div>
                        <form method="post" action="{{ route('activities.unregister', $activity) }}" data-confirm="Keluar daripada senarai menunggu aktiviti ini?">
                            @csrf
                            @method('delete')
                            <button class="btn btn-outline-secondary">Keluar Senarai Menunggu</button>
                        </form>
                    @else
                        <form method="post" action="{{ route('activities.register', $activity) }}">
                            @csrf
                            <button class="btn btn-danger">{{ $activity->hasCapacity() ? 'Daftar Aktiviti' : 'Sertai Senarai Menunggu' }}</button>
                        </form>
                    @endif
                @else
                    <div class="alert alert-danger mb-0">Pendaftaran belum dibuka atau sudah ditutup.</div>
                @endif
            </div>
        </div>

        @if($canManageAttendance)
        <div class="card">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Peserta Berdaftar</h2>
                <p class="small text-muted">Data akan dikemas kini automatik setiap 10 saat semasa aktiviti berlangsung.</p>
                <div id="activity-participants-list">
                @forelse($activity->activeRegistrations as $item)
                    <div class="border-bottom py-2 d-flex justify-content-between gap-3 align-items-center activity-participant-row" data-user-id="{{ $item->user_id }}">
                        <div>
                            <strong>{{ $item->user->name }}</strong>
                            <div class="small text-muted">Berdaftar pada {{ $item->registered_at->format('d/m/Y h:i A') }}</div>
                        </div>
                        @if(in_array($item->user_id, $attendedUserIds, true))
                            <span class="badge text-bg-success attendance-status">Hadir</span>
                        @elseif(auth()->user()->hasRole('admin', 'treasurer'))
                            <span class="badge text-bg-secondary attendance-status">Belum Hadir</span>
                            <form method="post" action="{{ route('activities.attendance.store', [$activity, $item]) }}" data-confirm="Tanda {{ $item->user->name }} sebagai hadir?">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">Tanda Hadir</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada pendaftaran.</p>
                @endforelse
                </div>

                @if($waitlistedCount > 0)
                    <hr>
                    <h3 class="h6">Senarai Menunggu ({{ $waitlistedCount }})</h3>
                    @foreach($activity->waitlistedRegistrations as $item)
                        <div class="border-bottom py-2">
                            <strong>{{ $item->user->name }}</strong>
                            <div class="small text-muted">Disertai pada {{ $item->registered_at->format('d/m/Y h:i A') }}</div>
                        </div>
                    @endforeach
                @endif

                @if($activity->guestRegistrations->isNotEmpty())
                    <hr>
                    <h3 class="h6">Tetamu Berdaftar ({{ $activity->guestRegistrations->where('status', 'registered')->count() }})</h3>
                    @foreach($activity->guestRegistrations as $guest)
                        <div class="border-bottom py-2">
                            <strong>{{ $guest->name }}</strong>
                            <div class="small text-muted">{{ $guest->email }} &middot; {{ $guest->phone }} &middot; {{ $guest->status === 'waitlisted' ? 'Senarai menunggu' : 'Berdaftar' }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif

                @if(auth()->user()->hasRole('admin'))
            @include('partials.audit-timeline', ['logs' => $timelineLogs])
        @endif
    </div>

    @if($activity->status === 'approved')
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-3">
                        <div>
                            <h2 class="h5 soft-panel-title mb-1">QR Kehadiran</h2>
                            <p class="small text-muted mb-0">QR dijana secara manual oleh bendahari untuk aktiviti ini.</p>
                        </div>
                        @if($canGenerateQr && now()->between($activity->date_time, $activity->end_time ?? $activity->date_time))
                            <form method="post" action="{{ route('activities.refresh-qr', $activity) }}" data-confirm="{{ $activity->qr_code_token ? 'Jana semula QR? QR lama tidak boleh digunakan lagi.' : 'Jana QR kehadiran untuk aktiviti ini?' }}">
                                @csrf
                                @method('patch')
                                <button class="btn btn-sm btn-outline-danger">{{ $activity->qr_code_token ? 'Jana Semula QR' : 'Jana QR' }}</button>
                            </form>
                        @endif
                    </div>
                    @if(now()->between($activity->date_time, $activity->end_time ?? $activity->date_time) && $activity->qr_code_token)
                        <div class="bg-white p-3 d-inline-block mb-2">{!! QrCode::size(180)->generate(route('attendance.scan', ['token' => $activity->qr_code_token])) !!}</div>
                        <p class="small text-muted">QR aktif sehingga aktiviti tamat.</p>
                    @elseif(now()->lt($activity->date_time))
                        <div class="alert alert-secondary mb-0">QR belum boleh dijana. Bendahari boleh menjana QR apabila aktiviti bermula.</div>
                    @elseif(now()->gt($activity->end_time ?? $activity->date_time))
                        <div class="alert alert-secondary mb-0">Aktiviti telah tamat dan QR kehadiran tidak lagi aktif.</div>
                    @else
                        <div class="alert alert-secondary mb-0">QR kehadiran belum dijana oleh bendahari.</div>
                    @endif
                    @if($canManageAttendance)
                        <hr>
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-2">
                            <h3 class="h6 mb-0">Kehadiran</h3>
                        </div>
                        @forelse($activity->attendances as $attendance)
                            <div class="small border-bottom py-1">{{ $attendance->user->name }} &middot; {{ $attendance->scanned_at->format('d/m/Y h:i A') }}</div>
                        @empty
                            <p class="text-muted small mb-0">Belum ada kehadiran.</p>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if($canManageAttendance && $activity->status === 'approved')
@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const endpoint = @json(route('activities.attendance-status', $activity));
    const endAt = new Date(@json(($activity->end_time ?? $activity->date_time)->toIso8601String()));
    const list = document.getElementById('activity-participants-list');
    const registeredCount = document.getElementById('activity-registered-count');
    const attendanceCount = document.getElementById('activity-attendance-count');

    const refreshAttendance = async () => {
        if (new Date() > endAt) return;
        try {
            const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            registeredCount.textContent = `${data.registered_count} / @json($capacity)`;
            attendanceCount.textContent = data.attended_count;
            data.participants.forEach((participant) => {
                const row = list.querySelector(`[data-user-id="${participant.user_id}"]`);
                if (!row) return;
                const badge = row.querySelector('.attendance-status');
                if (!badge) return;
                badge.textContent = participant.attended ? 'Hadir' : 'Belum Hadir';
                badge.className = `badge attendance-status ${participant.attended ? 'text-bg-success' : 'text-bg-secondary'}`;
            });
        } catch (error) {}
    };

    refreshAttendance();
    window.setInterval(refreshAttendance, 10000);
});
</script>
@endPushOnce
@endif
