@extends('layouts.app', ['title' => 'Notifikasi'])

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="dashboard-kicker mb-1">Pusat makluman</p>
        <h1 class="h3 mb-0">Notifikasi</h1>
        <p class="text-muted mb-0">Semak hebahan, peringatan dan status tindakan anda.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="{{ route('notifications.read-all') }}">
            @csrf
            @method('patch')
            <button class="btn btn-outline-danger">Tanda Semua Dibaca</button>
        </form>
        @if(auth()->user()->hasRole('admin', 'chairman'))
            <a class="btn btn-danger" href="{{ route('notifications.create') }}">
                <i class="bi bi-send me-2" aria-hidden="true"></i>Hantar Notifikasi
            </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        @forelse($notifications as $notification)
            <div class="border-bottom py-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $notification->title }}</strong>
                    <span class="badge bg-{{ $notification->is_read ? 'secondary' : 'danger' }}">
                        {{ $notification->is_read ? 'Dibaca' : 'Baharu' }}
                    </span>
                </div>
                <p class="mb-2">{{ $notification->message }}</p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if($notification->link)
                        <a class="btn btn-sm btn-outline-danger" href="{{ $notification->link }}">Buka</a>
                    @endif
                    @unless($notification->is_read)
                        <form method="post" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            @method('patch')
                            <button class="btn btn-sm btn-link px-0">Tanda dibaca</button>
                        </form>
                    @endunless
                </div>
            </div>
        @empty
            <div class="dashboard-empty-state my-3">
                <span class="stat-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
                <p class="text-muted mb-0">Tiada notifikasi buat masa ini.</p>
            </div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
