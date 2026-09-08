@extends('layouts.app', ['title' => 'Status Email'])

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="dashboard-kicker mb-1">Pentadbiran</p>
        <h1 class="h3 mb-0">Status Email</h1>
        <p class="text-muted mb-0">Pantau status email notification yang dihantar kepada ahli.</p>
    </div>
    <a class="btn btn-outline-danger" href="{{ route('notifications.index') }}">Kembali ke Notifikasi</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records align-middle mb-0">
            <thead><tr><th>Penerima</th><th>Notification</th><th>Status</th><th>Cubaan</th><th>Tarikh</th><th>Error</th></tr></thead>
            <tbody>
            @forelse($deliveries as $delivery)
                <tr>
                    <td data-label="Penerima">{{ $delivery->recipient }}</td>
                    <td data-label="Notification"><strong>{{ $delivery->notification?->title ?? str($delivery->event)->title() }}</strong><div class="small text-muted">{{ $delivery->user->name ?? 'Ahli' }}</div></td>
                    <td data-label="Status"><span class="badge {{ $delivery->status === 'sent' ? 'text-bg-success' : ($delivery->status === 'failed' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ str($delivery->status)->title() }}</span></td>
                    <td data-label="Cubaan">{{ $delivery->attempts }}</td>
                    <td data-label="Tarikh">{{ $delivery->sent_at?->format('d/m/Y H:i') ?? $delivery->created_at->format('d/m/Y H:i') }}</td>
                    <td data-label="Error">
                        <span class="small text-danger">{{ $delivery->error ?: '-' }}</span>
                        @if($delivery->status === 'failed')
                            <form method="post" action="{{ $delivery->notification ? route('admin.notifications.retry', $delivery->notification) : route('admin.email-deliveries.retry', $delivery) }}" class="mt-2">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger" type="submit">Cuba Semula</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Belum ada rekod email notification.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $deliveries->links() }}</div>
@endsection
