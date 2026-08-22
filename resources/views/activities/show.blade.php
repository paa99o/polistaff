@extends('layouts.app', ['title' => $activity->title])
@section('content')
@php
    $registration = $activity->registrations->firstWhere('user_id', auth()->id());
    $isRegistered = $registration?->status === 'registered';
    $isWaitlisted = $registration?->status === 'waitlisted';
    $registeredCount = $activity->activeRegistrations->count();
    $waitlistedCount = $activity->waitlistedRegistrations->count();
    $capacity = $activity->max_participants ?: 'Unlimited';
@endphp

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <h1 class="h4 mb-1">{{ $activity->title }}</h1>
                        <p class="text-muted mb-0">{{ $activity->date_time->format('d/m/Y h:i A') }} · {{ $activity->location }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(auth()->user()->hasRole('chairman','admin') && $activity->status !== 'approved')<form method="post" action="{{ route('activities.approve',$activity) }}">@csrf @method('patch')<button class="btn btn-sm btn-danger">Approve</button></form>@endif
                        @can('manage-activities')<a class="btn btn-sm btn-outline-danger" href="{{ route('activities.edit',$activity) }}">Edit</a>@endcan
                    </div>
                </div>
                <hr>
                <p>{{ $activity->description }}</p>

                <div class="row g-3 my-3">
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Status</div><strong>{{ $activity->status }}</strong></div></div>
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Registered</div><strong>{{ $registeredCount }} / {{ $capacity }}</strong></div></div>
                    <div class="col-md-4"><div class="technical-summary"><div class="stat-label">Attendance</div><strong>{{ $activity->attendances->count() }}</strong></div></div>
                </div>

                <div class="small text-muted mb-3">Registration: {{ $activity->registration_opens_at?->format('d/m/Y h:i A') ?? 'Anytime' }} - {{ $activity->registration_closes_at?->format('d/m/Y h:i A') ?? 'Until activity' }}<br>Attendance: {{ $activity->attendance_opens_at?->format('d/m/Y h:i A') ?? 'Anytime' }} - {{ $activity->attendance_closes_at?->format('d/m/Y h:i A') ?? 'No close time' }}</div>

                @if($activity->registrationIsOpen())
                    @if($isRegistered)
                        <div class="alert alert-success">Anda sudah berdaftar untuk aktiviti ini.</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <form method="post" action="{{ route('attendance.store') }}">@csrf<input type="hidden" name="token" value="{{ $activity->qr_code_token }}"><button class="btn btn-danger">Mark Attendance</button></form>
                            <form method="post" action="{{ route('activities.unregister', $activity) }}">@csrf @method('delete')<button class="btn btn-outline-secondary">Cancel Registration</button></form>
                        </div>
                    @elseif($isWaitlisted)
                        <div class="alert alert-info">Anda berada dalam waiting list. Anda akan dimaklumkan jika tempat kosong.</div>
                        <form method="post" action="{{ route('activities.unregister', $activity) }}">@csrf @method('delete')<button class="btn btn-outline-secondary">Cancel Waiting List</button></form>
                    @else
                        <form method="post" action="{{ route('activities.register', $activity) }}">@csrf<button class="btn btn-danger">{{ $activity->hasCapacity() ? 'Register Activity' : 'Join Waiting List' }}</button></form>
                    @endif
                @else
                    <div class="alert alert-danger mb-0">Pendaftaran belum dibuka atau sudah ditutup.</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Registered Participants</h2>
                @forelse($activity->activeRegistrations as $item)
                    <div class="border-bottom py-2"><strong>{{ $item->user->name }}</strong><div class="small text-muted">Registered {{ $item->registered_at->format('d/m/Y h:i A') }}</div></div>
                @empty
                    <p class="text-muted mb-0">Belum ada pendaftaran.</p>
                @endforelse
                @if($waitlistedCount > 0)<hr><h3 class="h6">Waiting List ({{ $waitlistedCount }})</h3>@foreach($activity->waitlistedRegistrations as $item)<div class="border-bottom py-2"><strong>{{ $item->user->name }}</strong><div class="small text-muted">Joined {{ $item->registered_at->format('d/m/Y h:i A') }}</div></div>@endforeach@endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">QR Attendance</h2>
                <div class="bg-white p-3 d-inline-block mb-2">{!! QrCode::size(180)->generate(route('attendance.scan', ['token' => $activity->qr_code_token])) !!}</div>
                <p class="small text-muted">QR ini merekod kehadiran hanya untuk ahli yang sudah berdaftar.</p>
                <code class="d-block text-break mb-2">{{ $activity->qr_code_token }}</code>
                <a class="small" href="{{ route('attendance.scan', ['token' => $activity->qr_code_token]) }}">{{ route('attendance.scan', ['token' => $activity->qr_code_token]) }}</a>
                <hr>
                <h3 class="h6">Kehadiran</h3>
                @forelse($activity->attendances as $attendance)
                    <div class="small border-bottom py-1">{{ $attendance->user->name }} · {{ $attendance->scanned_at->format('d/m/Y h:i A') }}</div>
                @empty
                    <p class="text-muted small mb-0">Belum ada kehadiran.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
