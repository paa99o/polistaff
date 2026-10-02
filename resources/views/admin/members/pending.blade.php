@extends('layouts.app', ['title' => 'Kelulusan Ahli'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Ahli Menunggu Kelulusan</h1>
    <a class="btn btn-outline-primary" href="{{ route('admin.index') }}">Pentadbiran</a>
</div>
<p class="text-muted">Semak permohonan baharu di sini. Apabila diluluskan, akaun ahli akan diaktifkan, emel pengesahan diselesaikan dan pemohon menerima emel untuk log masuk.</p>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Emel / Pengesahan</th>
                    <th>Jabatan</th>
                    <th>Maklumat</th>
                    <th>Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td data-label="Nama">{{ $member->name }}</td>
                        <td data-label="Emel / Pengesahan">
                            {{ $member->email }}
                            <span class="badge d-block mt-1 {{ $member->hasVerifiedEmail() ? 'text-bg-success' : 'text-bg-warning' }}">{{ $member->hasVerifiedEmail() ? 'Emel disahkan' : 'Emel akan disahkan semasa kelulusan' }}</span>
                        </td>
                        <td data-label="Jabatan">{{ $member->department }}</td>
                        <td data-label="Maklumat">
                            <div class="small">IC: {{ $member->ic_number ?: '-' }}</div>
                            <div class="small">Telefon: {{ $member->phone ?: '-' }}</div>
                            <div class="small text-truncate" style="max-width: 220px" title="{{ $member->address }}">Alamat: {{ $member->address ?: '-' }}</div>
                        </td>
                        <td data-label="Tindakan">
                            <div class="d-flex flex-column gap-2">
                                <form method="post" action="{{ route('admin.members.approve', $member) }}" data-confirm="Luluskan permohonan {{ $member->name }}?">
                                    @csrf
                                    @method('patch')
                                    <button class="btn btn-sm btn-primary">Luluskan</button>
                                </form>
                                <form method="post" action="{{ route('admin.members.reject', $member) }}" data-confirm="Tolak permohonan {{ $member->name }}? Emel akan dihantar kepada pemohon.">
                                    @csrf
                                    @method('patch')
                                    <label class="form-label small" for="reason-{{ $member->id }}">Sebab Ditolak</label>
                                    <textarea class="form-control form-control-sm @error('reason') is-invalid @enderror" id="reason-{{ $member->id }}" name="reason" rows="2" required>{{ old('reason') }}</textarea>
                                    @include('partials.errors', ['name' => 'reason'])
                                    <button class="btn btn-sm btn-outline-secondary mt-2">Tolak</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Tiada ahli menunggu kelulusan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $members->links() }}</div>
@endsection
