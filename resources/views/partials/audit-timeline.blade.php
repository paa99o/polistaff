<div class="card mt-4">
    <div class="card-body">
        <h2 class="h5 soft-panel-title">Timeline</h2>
        @forelse($logs as $log)
            <div class="border-bottom py-2">
                <div class="d-flex justify-content-between gap-3">
                    <strong>{{ ucfirst($log->action) }} · {{ $log->module }}</strong>
                    <span class="small text-muted">{{ $log->created_at->format('d/m/Y h:i A') }}</span>
                </div>
                <div class="small text-muted">{{ $log->description }}</div>
                <div class="small text-muted">Oleh: {{ $log->user->name ?? 'System' }}</div>
            </div>
        @empty
            <p class="text-muted mb-0">Belum ada rekod timeline.</p>
        @endforelse
    </div>
</div>
