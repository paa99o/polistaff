@extends('layouts.app', ['title' => 'Pratonton Kertas Kerja'])

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div><p class="stat-label mb-1">Semak sebelum cetak</p><h1 class="h3 mb-1">Kertas Kerja Program</h1><p class="text-muted mb-0">{{ $activity->title }} · Versi {{ $version->version }} · Templat {{ $version->template_version }} · Dijana {{ $version->generated_at->format('d/m/Y H:i') }}</p></div>
    <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="{{ route('activities.status-list', $activity->status) }}">Kembali</a><a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="{{ route('activities.paperwork.pdf', [$activity, $version, 'render' => 1]) }}">Buka / Cetak</a>@if($activity->status === 'approved')<a class="btn btn-primary" href="{{ route('activities.paperwork.pdf', [$activity, $version]) }}">Muat Turun PDF</a>@endif</div>
</div>
<div class="alert alert-info">Pratonton dan PDF menggunakan susunan kertas kerja rujukan. {{ $activity->status === 'approved' ? 'Dokumen ini telah diluluskan dan boleh dimuat turun.' : 'Anda boleh semak ringkasan, impak, objektif dan penutup sebelum menyimpan versi baharu.' }}</div>
@if($missingInformation)<div class="alert alert-warning"><h2 class="h6">Maklumat yang perlu dilengkapkan</h2><ul class="mb-0">@foreach($missingInformation as $item)<li>{{ $item }}</li>@endforeach</ul></div>@endif

<div class="card mb-4 paperwork-pdf-preview"><div class="card-body p-0"><iframe title="Pratonton PDF kertas kerja {{ $activity->title }}" src="{{ route('activities.paperwork.pdf', [$activity, $version, 'render' => 1]) }}#toolbar=1" loading="lazy"></iframe></div></div>

<div class="row g-4"><div class="col-xl-8"><div class="card"><div class="card-body">
    <h2 class="h5">Sunting kandungan kertas kerja</h2><p class="small text-muted">Perubahan disimpan sebagai versi baharu. Maklumat lain seperti peserta, jawatankuasa, jemputan, atur cara dan bajet datang daripada borang aktiviti.</p>
    <form method="post" action="{{ route('activities.paperwork.save', [$activity, $version]) }}">@csrf @method('put')
        <label class="form-label" for="paperworkSummary">Ringkasan Program</label><textarea class="form-control mb-3" id="paperworkSummary" name="summary" rows="6" required>{{ old('summary', $version->content['summary'] ?? $version->content['background'] ?? '') }}</textarea>
        <label class="form-label" for="paperworkObjectives">Objektif Program <span class="text-muted">(satu objektif bagi setiap baris)</span></label><textarea class="form-control mb-3" id="paperworkObjectives" name="objectives_text" rows="5">{{ old('objectives_text', implode("\n", $version->content['objectives'] ?? [])) }}</textarea>
        <label class="form-label" for="paperworkImpact">Hasil / Impak Program</label><textarea class="form-control mb-3" id="paperworkImpact" name="impact" rows="5" required>{{ old('impact', $version->content['impact'] ?? '') }}</textarea>
        <label class="form-label" for="paperworkClosing">Penutup</label><textarea class="form-control mb-3" id="paperworkClosing" name="closing" rows="5" required>{{ old('closing', $version->content['closing'] ?? '') }}</textarea>
        <div class="d-flex justify-content-end"><button class="btn btn-primary" type="submit">Simpan Versi Baharu</button></div>
    </form>
</div></div></div>
<aside class="col-xl-4"><div class="card"><div class="card-body"><h2 class="h5">Versi dokumen</h2><p class="small text-muted">Setiap simpanan atau jana semula disimpan sebagai versi berasingan.</p>
    <form method="post" action="{{ route('activities.paperwork.generate', $activity) }}" data-confirm="Jana semula dokumen daripada data borang asal? Suntingan semasa kekal dalam versi ini.">@csrf<button class="btn btn-outline-secondary w-100 mb-3" type="submit">Jana Semula daripada Borang</button></form>
    <ul class="list-group list-group-flush">@foreach($versions as $item)<li class="list-group-item px-0"><a href="{{ route('activities.paperwork.preview', [$activity, $item]) }}">Versi {{ $item->version }}</a><div class="small text-muted">{{ $item->generated_at->format('d/m/Y H:i') }} · {{ $item->generator?->name ?? 'Pengguna terdahulu' }}</div></li>@endforeach</ul>
    <hr><div class="small text-muted">Dokumen ini dipautkan kepada aktiviti #{{ $activity->id }}.</div>
</div></div></aside></div>
@endsection
