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
                        @if(auth()->user()->hasRole('chairman', 'admin') && $activity->status === 'pending_approval')
                            <form method="post" action="{{ route('activities.approve', $activity) }}" data-confirm="Luluskan aktiviti ini dan buka kepada ahli?">
                                @csrf
                                @method('patch')
                                <button class="btn btn-sm btn-danger">Luluskan</button>
                            </form>
                        @endif
                        @can('manage-activities')
                            <a class="btn btn-sm btn-outline-danger" href="{{ route('activities.edit', $activity) }}">Sunting</a>
                        @endcan
                    </div>
                </div>
                <hr>

                @if($activity->evidence_photo_path)
                    <figure class="activity-evidence mb-4">
                        <img src="{{ Storage::url($activity->evidence_photo_path) }}" alt="Foto bukti untuk {{ $activity->title }}">
                        <figcaption>Foto bukti aktiviti</figcaption>
                    </figure>
                @endif

                <p>{{ $activity->description }}</p>

                <div class="row g-3 my-3">
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Status</div><strong>{{ \App\Support\PolistaffLabels::status($activity->status) }}</strong></div></div>
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Berdaftar</div><strong>{{ $registeredCount }} / {{ $capacity }}</strong></div></div>
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Kehadiran</div><strong>{{ $activity->attendances_count }}</strong></div></div>
                </div>

                <div class="small text-muted mb-3">
                    Pendaftaran: {{ $activity->registration_opens_at?->format('d/m/Y h:i A') ?? 'Bila-bila masa' }} - {{ $activity->registration_closes_at?->format('d/m/Y h:i A') ?? 'Sehingga aktiviti' }}<br>
                    Kehadiran: {{ $activity->attendance_opens_at?->format('d/m/Y h:i A') ?? 'Bila-bila masa' }} - {{ $activity->attendance_closes_at?->format('d/m/Y h:i A') ?? 'Tiada waktu tutup' }}
                </div>

                @if($activity->registrationIsOpen())
                    @if($isRegistered)
                        <div class="alert alert-success">Anda sudah berdaftar untuk aktiviti ini.</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-danger" href="{{ route('attendance.scan') }}">Imbas QR Kehadiran</a>
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
                @forelse($activity->activeRegistrations as $item)
                    <div class="border-bottom py-2 d-flex justify-content-between gap-3 align-items-center">
                        <div>
                            <strong>{{ $item->user->name }}</strong>
                            <div class="small text-muted">Berdaftar pada {{ $item->registered_at->format('d/m/Y h:i A') }}</div>
                        </div>
                        @if(in_array($item->user_id, $attendedUserIds, true))
                            <span class="badge text-bg-success">Hadir</span>
                        @elseif(auth()->user()->hasRole('admin', 'chairman', 'treasurer'))
                            <form method="post" action="{{ route('activities.attendance.store', [$activity, $item]) }}" data-confirm="Tanda {{ $item->user->name }} sebagai hadir?">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">Tanda Hadir</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada pendaftaran.</p>
                @endforelse

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
            </div>
        </div>
        @endif

        @if(auth()->user()->hasRole('chairman', 'admin'))
            @include('partials.audit-timeline', ['logs' => $timelineLogs])
        @endif
    </div>

    @if($canManageAttendance)
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-3">
                        <div>
                            <h2 class="h5 soft-panel-title mb-1">QR Kehadiran</h2>
                            <p class="small text-muted mb-0">Jana QR semasa aktiviti bermula. QR lama akan menjadi tidak sah.</p>
                        </div>
                        <form method="post" action="{{ route('activities.refresh-qr', $activity) }}" data-confirm="Jana QR baharu? QR lama tidak boleh digunakan lagi.">
                            @csrf
                            @method('patch')
                            <button class="btn btn-sm btn-outline-danger">Jana QR</button>
                        </form>
                    </div>
                    <div class="bg-white p-3 d-inline-block mb-2">{!! QrCode::size(180)->generate(route('attendance.scan', ['token' => $activity->qr_code_token])) !!}</div>
                    <p class="small text-muted">QR ini merekod kehadiran hanya untuk ahli yang sudah berdaftar.</p>
                    <code class="d-block text-break mb-2">{{ $activity->qr_code_token }}</code>
                    <a class="small" href="{{ route('attendance.scan', ['token' => $activity->qr_code_token]) }}">{{ route('attendance.scan', ['token' => $activity->qr_code_token]) }}</a>
                    <hr>
                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-2">
                        <h3 class="h6 mb-0">Kehadiran</h3>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.activities.attendance.csv', $activity) }}">CSV Kehadiran</a>
                    </div>
                    @forelse($activity->attendances as $attendance)
                        <div class="small border-bottom py-1">{{ $attendance->user->name }} &middot; {{ $attendance->scanned_at->format('d/m/Y h:i A') }}</div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada kehadiran.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
