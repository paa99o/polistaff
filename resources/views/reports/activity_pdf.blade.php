<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 42px 48px; }
        body { color: #253746; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.5; }
        .topline { border-bottom: 3px solid #4f8074; margin-bottom: 24px; padding-bottom: 13px; }
        .brand { color: #4f8074; font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        h1 { color: #253746; font-size: 23px; margin: 6px 0 3px; }
        .subtitle { color: #718096; font-size: 10px; margin: 0; }
        h2 { border-bottom: 1px solid #d9e3e1; color: #315e55; font-size: 13px; margin: 22px 0 10px; padding-bottom: 6px; page-break-after: avoid; }
        h3 { color: #315e55; font-size: 10px; margin: 13px 0 6px; page-break-after: avoid; }
        p { margin: 4px 0 8px; }
        table { border-collapse: collapse; margin: 7px 0 12px; width: 100%; }
        th, td { border: 1px solid #d5dfdf; padding: 7px 8px; text-align: left; vertical-align: top; }
        th { background: #edf3f1; color: #315e55; font-weight: bold; }
        .facts th { width: 24%; }
        .facts td { width: 26%; }
        .summary { background: #f4f8f6; border-left: 3px solid #4f8074; margin: 8px 0 14px; padding: 10px 12px; white-space: pre-wrap; }
        .pill { background: #edf3f1; border: 1px solid #d5dfdf; border-radius: 10px; display: inline-block; margin: 2px 3px 2px 0; padding: 4px 8px; }
        .objectives { margin: 0; padding-left: 21px; }
        .objectives li { margin: 0 0 5px; padding-left: 3px; }
        .photo { border: 1px solid #d5dfdf; border-radius: 5px; max-height: 420px; max-width: 100%; }
        .empty { color: #718096; font-style: italic; }
        .footer { border-top: 1px solid #d5dfdf; color: #718096; font-size: 8px; margin-top: 22px; padding-top: 7px; }
        .page-number:after { content: counter(page); }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $proposal = $activity->proposal_data ?? [];
    $participantLabels = ['Staff' => 'Staf', 'Lecturer' => 'Pensyarah', 'External Community' => 'Komuniti luar', 'Others' => 'Lain-lain', 'Student' => 'Pelajar'];
    $participants = collect($proposal['target_participants'] ?? [])->filter()->map(fn ($item) => $participantLabels[$item] ?? $item);
    $purposes = collect($proposal['purposes'] ?? [])->filter();
    $courseCategories = collect($proposal['course_categories'] ?? [])->filter();
    $objectives = collect($proposal['objectives'] ?? [])->filter();
    $tentative = collect($proposal['tentative'] ?? [])->filter(fn ($row) => is_array($row) && filled($row['description'] ?? null));
    $committee = collect($proposal['committee'] ?? [])->filter(fn ($row) => is_array($row) && (filled($row['name'] ?? null) || filled($row['position'] ?? null)));
    $speakers = collect($proposal['speakers'] ?? [])->filter(fn ($row) => is_array($row) && (filled($row['name'] ?? null) || filled($row['position'] ?? null) || filled($row['institution'] ?? null)));
    $budgetItems = collect($proposal['budget_items'] ?? [])->filter(fn ($row) => is_array($row) && filled($row['description'] ?? null));
    $budgetTotal = $budgetItems->sum(fn ($row) => (float) ($row['quantity'] ?? 0) * (float) ($row['estimated_cost'] ?? 0));
@endphp

<div class="topline">
    <div class="brand">Portal Pengurusan Kelab Staf · POLIBEST</div>
    <h1>Laporan Aktiviti</h1>
    <p class="subtitle">Ringkasan program dan rekod pelaksanaan</p>
</div>

<h2>Maklumat aktiviti</h2>
<table class="facts">
    <tr><th>Nama aktiviti</th><td colspan="3">{{ $activity->title }}</td></tr>
    <tr><th>Tarikh</th><td>{{ $activity->date_time->format('d/m/Y') }}{{ $activity->end_time && $activity->end_time->format('Y-m-d') !== $activity->date_time->format('Y-m-d') ? ' hingga '.$activity->end_time->format('d/m/Y') : '' }}</td><th>Masa</th><td>{{ $activity->date_time->format('h:i A') }}{{ $activity->end_time ? ' – '.$activity->end_time->format('h:i A') : '' }}</td></tr>
    <tr><th>Lokasi</th><td>{{ $activity->location ?: '–' }}</td><th>Penganjur</th><td>{{ $activity->organizing_unit ?: '–' }}</td></tr>
    <tr><th>Pegawai bertanggungjawab</th><td>{{ $activity->person_in_charge ?: '–' }}</td><th>Peringkat</th><td>{{ $proposal['program_level'] ?? '–' }}{{ filled($proposal['session'] ?? null) ? ' · '.$proposal['session'] : '' }}</td></tr>
    <tr><th>Anggaran peserta</th><td>{{ $activity->expected_participants ?? $activity->max_participants ?? '–' }}</td><th>Jumlah berdaftar</th><td>{{ $activity->active_registrations_count }}</td></tr>
    <tr><th>Jumlah kehadiran</th><td colspan="3">{{ $activity->attendances_count }}</td></tr>
</table>

@if(filled($proposal['summary'] ?? null))
    <h2>Ringkasan program</h2>
    <div class="summary">{{ $proposal['summary'] }}</div>
@endif

@if($purposes->isNotEmpty() || $courseCategories->isNotEmpty())
    <h2>Tujuan dan bidang program</h2>
    @if($purposes->isNotEmpty())<h3>Penjajaran program</h3><div>@foreach($purposes as $purpose)<span class="pill">{{ $purpose }}</span>@endforeach</div>@endif
    @if($courseCategories->isNotEmpty())<h3>Kategori kursus</h3><div>@foreach($courseCategories as $category)<span class="pill">{{ $category }}</span>@endforeach</div>@endif
@endif

@if($participants->isNotEmpty())
    <h2>Sasaran peserta</h2>
    <div>@foreach($participants as $participant)<span class="pill">{{ $participant }}</span>@endforeach</div>
@endif

@if($objectives->isNotEmpty())
    <h2>Objektif program</h2>
    <ol class="objectives">@foreach($objectives as $objective)<li>{{ $objective }}</li>@endforeach</ol>
@endif

@if(filled($proposal['impact'] ?? null))
    <h2>Hasil dan impak</h2>
    <div class="summary">{{ $proposal['impact'] }}</div>
@endif

@if($tentative->isNotEmpty())
    <h2>Tentatif program</h2>
    <table><thead><tr><th style="width:22%">Tarikh</th><th style="width:18%">Masa</th><th>Aktiviti</th></tr></thead><tbody>
        @foreach($tentative as $row)
            <tr><td>{{ filled($row['date'] ?? null) ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : $activity->date_time->format('d/m/Y') }}</td><td>{{ filled($row['time'] ?? null) ? \Carbon\Carbon::parse($row['time'])->format('h:i A') : '–' }}</td><td>{{ $row['description'] }}</td></tr>
        @endforeach
    </tbody></table>
@endif

@if($committee->isNotEmpty())
    <h2>Jawatankuasa program</h2>
    <table><thead><tr><th style="width:9%">Bil.</th><th>Nama</th><th>Jawatan / Peranan</th></tr></thead><tbody>
        @foreach($committee as $row)<tr><td>{{ $loop->iteration }}</td><td>{{ $row['name'] ?? '–' }}</td><td>{{ $row['position'] ?? '–' }}</td></tr>@endforeach
    </tbody></table>
@endif

@if($speakers->isNotEmpty())
    <h2>Penceramah dan jemputan</h2>
    <table><thead><tr><th>Nama</th><th>Jawatan</th><th>Gred</th><th>Jabatan / Institusi</th></tr></thead><tbody>
        @foreach($speakers as $speaker)<tr><td>{{ $speaker['name'] ?? '–' }}</td><td>{{ $speaker['position'] ?? '–' }}</td><td>{{ $speaker['grade'] ?? '–' }}</td><td>{{ $speaker['institution'] ?? '–' }}</td></tr>@endforeach
    </tbody></table>
@endif

@if(filled($proposal['finance_source'] ?? null) || $budgetItems->isNotEmpty())
    <h2>Maklumat kewangan</h2>
    @if(filled($proposal['finance_source'] ?? null))<p><strong>Sumber kewangan:</strong> {{ $proposal['finance_source'] }}</p>@endif
    @if($budgetItems->isNotEmpty())
        <table><thead><tr><th>Perkara</th><th style="width:12%">Kuantiti</th><th style="width:20%">Kos seunit</th><th style="width:20%">Jumlah</th><th style="width:16%">Kod OS</th></tr></thead><tbody>
            @foreach($budgetItems as $row)
                @php($rowTotal = (float) ($row['quantity'] ?? 0) * (float) ($row['estimated_cost'] ?? 0))
                <tr><td>{{ $row['description'] }}</td><td>{{ $row['quantity'] ?? '–' }}</td><td>RM {{ number_format((float) ($row['estimated_cost'] ?? 0), 2) }}</td><td>RM {{ number_format($rowTotal, 2) }}</td><td>{{ $row['source_code'] ?? '–' }}</td></tr>
            @endforeach
            <tr><th colspan="3" style="text-align:right">Jumlah anggaran</th><th colspan="2">RM {{ number_format($budgetTotal, 2) }}</th></tr>
        </tbody></table>
    @endif
    @if(filled($proposal['kulpl_review'] ?? null))<p><strong>Semakan KULPL:</strong> {{ $proposal['kulpl_review'] }}</p>@endif
@endif

@if(filled($proposal['closing'] ?? null))
    <h2>Penutup</h2>
    <div class="summary">{{ $proposal['closing'] }}</div>
@endif

@if($reportPhoto)
    <h2>Gambar aktiviti</h2>
    <img class="photo" src="{{ $reportPhoto }}" alt="Gambar aktiviti">
@endif

<h2>Rekod kehadiran</h2>
<table><thead><tr><th style="width:8%">Bil.</th><th>Nama</th><th>E-mel</th><th style="width:24%">Masa hadir</th></tr></thead><tbody>
    @forelse($attendances as $attendance)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $attendance->user->name }}</td><td>{{ $attendance->user->email }}</td><td>{{ $attendance->scanned_at->format('d/m/Y h:i A') }}</td></tr>
    @empty
        <tr><td colspan="4" class="empty">Tiada rekod kehadiran.</td></tr>
    @endforelse
</tbody></table>

<div class="footer">Laporan Aktiviti POLIBEST · Dijana {{ now()->format('d/m/Y h:i A') }}<span style="float:right">Halaman <span class="page-number"></span></span></div>
</body>
</html>
