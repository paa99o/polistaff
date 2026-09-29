@extends('layouts.app', ['title' => 'Pengurusan Pengguna'])

@section('content')
<section class="admin-page admin-users-page">
    <div class="admin-hero">
        <div>
            <p class="stat-label mb-1">Direktori POLIBEST</p>
            <h1>Pengurusan Pengguna</h1>
            <p>Cari ahli dan kemas kini peranan, status keahlian atau baki yuran.</p>
        </div>
        <div class="admin-hero-actions no-print">
            <a class="btn btn-outline-primary" href="{{ route('admin.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Sistem Pentadbiran</a>
        </div>
    </div>

    <article class="card admin-panel">
        <div class="card-body">
            <form class="admin-user-filter no-print" method="GET" action="{{ route('admin.users') }}">
                <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama, emel atau jabatan">
                <select class="form-select" name="role">
                    <option value="">Semua Role</option>
                    @foreach(['member','treasurer','admin'] as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ \App\Support\PolistaffLabels::role($role) }}</option>
                    @endforeach
                </select>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    @foreach(['pending','active','inactive'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Support\PolistaffLabels::status($status) }}</option>
                    @endforeach
                </select>
                <select class="form-select" name="profile">
                    <option value="">Semua Profil</option>
                    <option value="complete" @selected(request('profile') === 'complete')>Profil Lengkap</option>
                    <option value="incomplete" @selected(request('profile') === 'incomplete')>Belum Lengkap</option>
                </select>
                <button class="btn btn-primary" type="submit">Tapis</button>
            </form>

            <div class="table-responsive">
                <table class="table mobile-records align-middle admin-user-table mb-0">
                    <thead><tr><th>Ahli</th><th>Jabatan</th><th>Peranan</th><th>Status</th><th>Baki Yuran</th><th></th></tr></thead>
                    <tbody>
                    @forelse($users as $user)
                        @php
                            $userInitials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($name) => mb_strtoupper(mb_substr($name, 0, 1)))->implode('');
                            $statusClass = \App\Support\PolistaffLabels::statusClass($user->membership_status);
                            $profileComplete = $user->profileIsComplete();
                        @endphp
                        <tr>
                            <td data-label="Ahli">
                                <div class="user-list-profile">
                                    <span class="user-list-avatar">
                                        @if($user->profile_photo_path)
                                            <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="Gambar profil {{ $user->name }}">
                                        @else
                                            {{ $userInitials ?: 'PS' }}
                                        @endif
                                    </span>
                                    <span>
                                        <strong>{{ $user->name }}</strong>
                                        <span class="small text-muted d-block">{{ $user->email }}</span>
                                        <span class="badge {{ $profileComplete ? 'text-bg-success' : 'text-bg-warning' }} mt-1">{{ $profileComplete ? 'Profil lengkap' : 'Belum lengkap' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Jabatan">{{ $user->department ?? '-' }}</td>
                            <td data-label="Peranan"><span class="badge text-bg-light">{{ \App\Support\PolistaffLabels::role($user->role) }}</span></td>
                            <td data-label="Status"><span class="badge {{ $statusClass }}">{{ \App\Support\PolistaffLabels::status($user->membership_status) }}</span></td>
                            <td data-label="Baki Yuran">RM {{ number_format((float) $user->fee_balance, 2) }}</td>
                            <td data-label="Tindakan">
                                <form method="post" action="{{ route('admin.users.update', $user) }}" class="admin-user-update" data-confirm="Kemaskini peranan, status atau baki yuran untuk {{ $user->name }}?">
                                    @csrf
                                    @method('patch')
                                    <select class="form-select form-select-sm" name="role" aria-label="Role {{ $user->name }}">
                                        @foreach(['member','treasurer','admin'] as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ \App\Support\PolistaffLabels::role($role) }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select form-select-sm" name="membership_status" aria-label="Status {{ $user->name }}">
                                        @foreach(['pending','active','inactive'] as $status)
                                            <option value="{{ $status }}" @selected($user->membership_status === $status)>{{ \App\Support\PolistaffLabels::status($status) }}</option>
                                        @endforeach
                                    </select>
                                    <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="fee_balance" value="{{ $user->fee_balance }}" aria-label="Baki yuran {{ $user->name }}">
                                    <button class="btn btn-sm btn-primary" type="submit">Kemaskini</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Tiada pengguna dijumpai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <div class="mt-3">{{ $users->links() }}</div>
</section>
@endsection
