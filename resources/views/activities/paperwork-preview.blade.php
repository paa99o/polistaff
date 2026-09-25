@extends('layouts.app', ['title' => 'Pratonton Kertas Kerja'])

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div><p class="stat-label mb-1">Semak sebelum muat turun</p><h1 class="h3 mb-1">Kertas Kerja Program</h1><p class="text-muted mb-0">{{ $activity->title }} · Versi {{ $version->version }} · Templat {{ $version->template_version }} · Dijana {{ $version->generated_at->format('d/m/Y H:i') }}</p></div>
    <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="{{ route('activities.status-list', $activity->status) }}">Kembali</a><a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="{{ route('activities.paperwork.pdf', [$activity, $version, 'render' => 1]) }}">Buka / Cetak</a>
        @if($activity->status === 'approved')<a class="btn btn-danger" href="{{ route('activities.paperwork.pdf', [$activity, $version]) }}">Muat Turun PDF</a>@endif
    </div>
</div>

<div class="alert alert-info">Pratonton dijana daripada versi kertas kerja yang disimpan. {{ $activity->status === 'approved' ? 'Dokumen ini telah diluluskan dan boleh dimuat turun.' : 'Semak kandungan dan simpan suntingan di bawah. PDF akhir boleh dimuat turun selepas aktiviti diluluskan.' }}</div>

@if($missingInformation)
    <div class="alert alert-warning"><h2 class="h6">Maklumat yang perlu dilengkapkan</h2><ul class="mb-0">@foreach($missingInformation as $item)<li>{{ $item }}</li>@endforeach</ul></div>
@endif

<div class="card mb-4 paperwork-pdf-preview"><div class="card-body p-0"><iframe title="Pratonton PDF kertas kerja {{ $activity->title }}" src="{{ route('activities.paperwork.pdf', [$activity, $version, 'render' => 1]) }}#toolbar=1" loading="lazy"></iframe></div></div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card"><div class="card-body">
            <h2 class="h5">Sunting kandungan dokumen</h2><p class="small text-muted">Simpan perubahan untuk menghasilkan versi baharu; kemudian pratonton PDF akan menggunakan versi terkini.</p>
            <form method="post" action="{{ route('activities.paperwork.save', [$activity, $version]) }}">
                @csrf @method('put')
                <div class="paperwork-preview-document">
                    <div class="text-center mb-4"><h2 class="h4 fw-bold">KERTAS KERJA PROGRAM</h2><div class="h5">{{ $version->content['title'] }}</div></div>
                    <h3>1.0 MAKLUMAT PROGRAM</h3>
                    <dl class="row"><dt class="col-sm-4">Jenis / Kategori</dt><dd class="col-sm-8">{{ $version->content['activity_type'] ?: '-' }} / {{ $version->content['program_category'] ?: '-' }}</dd><dt class="col-sm-4">Penganjur / Unit</dt><dd class="col-sm-8">{{ $version->content['organizing_unit'] ?: '-' }}</dd><dt class="col-sm-4">Tarikh</dt><dd class="col-sm-8">{{ $version->content['start_date'] ?: '-' }}{{ ($version->content['end_date'] ?? null) && $version->content['end_date'] !== $version->content['start_date'] ? ' hingga '.$version->content['end_date'] : '' }}</dd><dt class="col-sm-4">Masa</dt><dd class="col-sm-8">{{ $version->content['start_time'] ?: '-' }} – {{ $version->content['end_time'] ?: '-' }}</dd><dt class="col-sm-4">Tempat</dt><dd class="col-sm-8">{{ $version->content['location'] ?: '-' }}</dd><dt class="col-sm-4">Mod</dt><dd class="col-sm-8">{{ $version->content['implementation_mode'] ?: '-' }}</dd></dl>
                    <h3>2.0 LATAR BELAKANG PROGRAM</h3><label class="visually-hidden" for="paperworkBackground">Latar belakang</label><textarea class="form-control mb-3" id="paperworkBackground" name="background" rows="5" required>{{ old('background', $version->content['background'] ?? '') }}</textarea>
                    <h3>3.0 OBJEKTIF PROGRAM</h3><p class="small text-muted">Satu objektif bagi setiap baris. Boleh disunting sebelum simpan.</p><textarea class="form-control mb-3" name="objectives_text" rows="4">{{ old('objectives_text', implode("\n", $version->content['objectives'] ?? [])) }}</textarea>
                    <h3>4.0 SASARAN PESERTA</h3><p>{{ implode(', ', $version->content['target_participants'] ?? []) ?: '-' }} · {{ $version->content['expected_participants'] ?? '-' }} orang</p><p>{{ $version->content['participant_criteria'] ?: '-' }}</p>
                    <h3>5.0 TENTATIF PROGRAM</h3><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Masa</th><th>Aktiviti</th></tr></thead><tbody>@forelse($version->content['tentative'] ?? [] as $row)<tr><td>{{ $row['time'] ?? '-' }}</td><td>{{ $row['description'] ?? '-' }}</td></tr>@empty<tr><td colspan="2" class="text-muted">Belum diisi</td></tr>@endforelse</tbody></table></div>
                    <h3>6.0 JAWATANKUASA PROGRAM</h3><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Bil</th><th>Nama</th><th>Jawatan</th></tr></thead><tbody>@forelse($version->content['committee'] ?? [] as $i => $row)<tr><td>{{ $i + 1 }}</td><td>{{ $row['name'] ?? '-' }}</td><td>{{ $row['position'] ?? '-' }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Belum diisi</td></tr>@endforelse</tbody></table></div>
                    <h3>7.0 ANGGARAN PERBELANJAAN</h3><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Bil</th><th>Perkara</th><th>Kuantiti</th><th>Kos Seunit</th><th>Jumlah</th></tr></thead><tbody>@php($budgetTotal = 0)@forelse($version->content['budget_items'] ?? [] as $i => $row)@php($lineTotal = (float)($row['quantity'] ?? 0) * (float)($row['estimated_cost'] ?? 0))@php($budgetTotal += $lineTotal)<tr><td>{{ $i + 1 }}</td><td>{{ $row['description'] ?? '-' }}</td><td>{{ $row['quantity'] ?? 0 }}</td><td>RM {{ number_format((float)($row['estimated_cost'] ?? 0), 2) }}</td><td>RM {{ number_format($lineTotal, 2) }}</td></tr>@empty<tr><td colspan="5" class="text-muted">Belum diisi</td></tr>@endforelse<tr><th colspan="4">Jumlah Perbelanjaan</th><th>RM {{ number_format($budgetTotal, 2) }}</th></tr></tbody></table></div>
                    <h3>8.0 SUMBER KEWANGAN</h3><p>{{ implode(', ', $version->content['funding_sources'] ?? []) ?: '-' }}</p>
                    <h3>9.0 PENUTUP</h3><label class="visually-hidden" for="paperworkClosing">Penutup</label><textarea class="form-control mb-3" id="paperworkClosing" name="closing" rows="4" required>{{ old('closing', $version->content['closing'] ?? '') }}</textarea>
                    <h3>10.0 PENGESAHAN / KELULUSAN</h3><div class="row text-center mt-5"><div class="col-4">Disediakan oleh:<br><br><br><strong>{{ $version->content['prepared_by'] ?? '-' }}</strong></div><div class="col-4">Disemak oleh:<br><br><br><strong>{{ $version->content['checked_by'] ?? '-' }}</strong></div><div class="col-4">Diluluskan oleh:<br><br><br><strong>{{ $version->content['approved_by'] ?? '-' }}</strong></div></div>
                </div>
                <div class="d-flex justify-content-end mt-3"><button class="btn btn-danger" type="submit">Simpan Draf Baharu</button></div>
            </form>
        </div></div>
    </div>
    <aside class="col-xl-4"><div class="card"><div class="card-body"><h2 class="h5">Versi dokumen</h2><p class="small text-muted">Setiap simpanan atau jana semula disimpan sebagai versi berasingan.</p><form method="post" action="{{ route('activities.paperwork.generate', $activity) }}" data-confirm="Jana semula dokumen daripada data borang asal? Suntingan semasa kekal dalam versi ini.">@csrf<button class="btn btn-outline-secondary w-100 mb-3" type="submit">Jana Semula daripada Borang</button></form>
        <ul class="list-group list-group-flush">@foreach($versions as $item)<li class="list-group-item px-0"><a href="{{ route('activities.paperwork.preview', [$activity, $item]) }}">Versi {{ $item->version }}</a><div class="small text-muted">{{ $item->generated_at->format('d/m/Y H:i') }} · {{ $item->generator?->name ?? 'Pengguna terdahulu' }}</div></li>@endforeach</ul>
        <hr><div class="small text-muted">Dokumen ini dipautkan kepada aktiviti #{{ $activity->id }}. Tarikh, jumlah peserta dan bajet diambil daripada rekod borang, bukan dijana secara automatik.</div></div></div></aside>
</div>
@endsection
