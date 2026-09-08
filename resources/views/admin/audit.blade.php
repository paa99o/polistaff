@extends('layouts.app', ['title' => 'Audit Trail'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Audit Trail</h1>
        <p class="text-muted mb-0">Jejak perubahan penting untuk kawalan dalaman dan mengurangkan human error.</p>
    </div>
    <a class="btn btn-outline-danger" href="{{ route('admin.index') }}">Admin System</a>
</div>

<div class="card audit-panel">
    <div class="card-body">
        <div class="audit-panel-heading">
            <div>
                <p class="stat-label mb-1">Rekod sistem</p>
                <h2 class="h5 mb-0">Aktiviti Terkini</h2>
            </div>
            <span class="audit-count">{{ $logs->total() }} rekod</span>
        </div>

        <form class="audit-filter mt-4">
            <label>
                <span>Modul</span>
                <input class="form-control" name="module" value="{{ request('module') }}" placeholder="Contoh: Profile">
            </label>
            <label>
                <span>Tindakan</span>
                <input class="form-control" name="action" value="{{ request('action') }}" placeholder="Contoh: updated">
            </label>
            <label>
                <span>Tarikh</span>
                <input class="form-control" type="date" name="date" value="{{ request('date') }}">
            </label>
            <button class="btn btn-danger align-self-end" type="submit"><i class="bi bi-funnel me-2" aria-hidden="true"></i>Tapis</button>
        </form>

        <div class="audit-timeline mt-4">
            @forelse($logs as $log)
                @php
                    $actionStyle = match ($log->action) {
                        'created', 'approved', 'enabled', 'verified-email' => ['icon' => 'bi-check-lg', 'class' => 'audit-success'],
                        'rejected', 'disabled', 'deleted' => ['icon' => 'bi-x-lg', 'class' => 'audit-danger'],
                        'email-sent', 'sent', 'retried' => ['icon' => 'bi-send', 'class' => 'audit-info'],
                        default => ['icon' => 'bi-pencil', 'class' => 'audit-neutral'],
                    };
                @endphp
                <article class="audit-timeline-item">
                    <div class="audit-timeline-marker {{ $actionStyle['class'] }}"><i class="bi {{ $actionStyle['icon'] }}" aria-hidden="true"></i></div>
                    <div class="audit-timeline-content">
                        <div class="audit-timeline-topline">
                            <div>
                                <strong>{{ $log->description }}</strong>
                                <span class="audit-module">{{ $log->module }}</span>
                            </div>
                            <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y h:i A') }}</time>
                        </div>
                        <div class="audit-meta">
                            <span><i class="bi bi-person" aria-hidden="true"></i>{{ $log->user->name ?? 'System' }}</span>
                            <span><i class="bi bi-tag" aria-hidden="true"></i>{{ $log->action }}</span>
                            <span><i class="bi bi-globe2" aria-hidden="true"></i>{{ $log->ip_address ?? 'System' }}</span>
                        </div>
                        @if($log->changes)
                            <details class="audit-changes">
                                <summary>Lihat perubahan</summary>
                                <pre>{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @endif
                    </div>
                </article>
            @empty
                <div class="audit-empty-state">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <strong>Belum ada rekod audit</strong>
                    <span>Perubahan penting sistem akan dipaparkan di sini.</span>
                </div>
            @endforelse
        </div>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
