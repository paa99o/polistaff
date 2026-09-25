<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #334155; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { color: #1557D8; font-size: 20px; }
        h2 { font-size: 14px; margin-top: 20px; }
        table { border-collapse: collapse; margin-bottom: 12px; width: 100%; }
        td, th { border: 1px solid #94a3b8; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; }
        .photo { border: 1px solid #94a3b8; max-height: 300px; max-width: 480px; }
    </style>
</head>
<body>
    <h1>Laporan Aktiviti POLIBEST</h1>
    <table>
        <tr><th>Nama Aktiviti</th><td>{{ $activity->title }}</td></tr>
        <tr><th>Tarikh</th><td>{{ $activity->date_time->format('d/m/Y') }}</td></tr>
        <tr><th>Masa</th><td>{{ $activity->date_time->format('h:i A') }} - {{ $activity->end_time?->format('h:i A') }}</td></tr>
        <tr><th>Lokasi</th><td>{{ $activity->location }}</td></tr>
        <tr><th>Jumlah Berdaftar</th><td>{{ $activity->active_registrations_count }}</td></tr>
        <tr><th>Jumlah Kehadiran</th><td>{{ $activity->attendances_count }}</td></tr>
    </table>

    @if($activity->activity_type || $activity->program_category || $activity->proposal_data)
        <h2>Maklumat Proposal Program</h2>
        <table>
            <tr><th>Jenis Aktiviti</th><td>{{ $activity->activity_type ?? '-' }}</td></tr>
            <tr><th>Kategori</th><td>{{ $activity->program_category ?? '-' }}</td></tr>
            <tr><th>Jabatan / Unit Penganjur</th><td>{{ $activity->organizing_unit ?? '-' }}</td></tr>
            <tr><th>Pegawai Bertanggungjawab</th><td>{{ $activity->person_in_charge ?? '-' }}</td></tr>
            <tr><th>Penerangan / Latar Belakang</th><td>{{ $activity->description ?? '-' }}</td></tr>
            <tr><th>Mod Pelaksanaan</th><td>{{ $activity->implementation_mode ?? '-' }}</td></tr>
            <tr><th>Anggaran Peserta</th><td>{{ $activity->expected_participants ?? $activity->max_participants ?? '-' }}</td></tr>
            <tr><th>Sasaran Peserta</th><td>{{ implode(', ', $activity->proposal_data['target_participants'] ?? []) ?: '-' }}</td></tr>
            <tr><th>Kriteria Peserta</th><td>{{ $activity->participant_criteria ?: '-' }}</td></tr>
        </table>

        @if(!empty($activity->proposal_data['objectives'] ?? []))
            <h2>Objektif Program</h2>
            <ol>@foreach($activity->proposal_data['objectives'] as $objective)<li>{{ $objective }}</li>@endforeach</ol>
        @endif
        @if(!empty($activity->proposal_data['tentative'] ?? []))
            <h2>Tentatif Program</h2>
            <table><tr><th>Masa</th><th>Keterangan Aktiviti</th></tr>@foreach($activity->proposal_data['tentative'] as $row)<tr><td>{{ $row['time'] ?? '-' }}</td><td>{{ $row['description'] ?? '-' }}</td></tr>@endforeach</table>
        @endif
        @if(!empty($activity->proposal_data['committee'] ?? []))
            <h2>Jawatankuasa Program</h2>
            <table><tr><th>Nama</th><th>Jawatan / Peranan</th></tr>@foreach($activity->proposal_data['committee'] as $row)<tr><td>{{ $row['name'] ?? '-' }}</td><td>{{ $row['position'] ?? '-' }}</td></tr>@endforeach</table>
        @endif
        @if(!empty($activity->proposal_data['budget_items'] ?? []))
            <h2>Anggaran Bajet</h2>
            <table><tr><th>Item</th><th>Kuantiti</th><th>Kos Seunit</th><th>Jumlah</th></tr>
                @php($budgetTotal = 0)
                @foreach($activity->proposal_data['budget_items'] as $row)
                    @php($rowTotal = (float) ($row['quantity'] ?? 0) * (float) ($row['estimated_cost'] ?? 0))
                    @php($budgetTotal += $rowTotal)
                    <tr><td>{{ $row['description'] ?? '-' }}</td><td>{{ $row['quantity'] ?? '-' }}</td><td>RM {{ number_format((float) ($row['estimated_cost'] ?? 0), 2) }}</td><td>RM {{ number_format($rowTotal, 2) }}</td></tr>
                @endforeach
                <tr><th colspan="3">Jumlah Anggaran</th><th>RM {{ number_format($budgetTotal, 2) }}</th></tr>
            </table>
        @endif
        @if(!empty($activity->proposal_data['funding_sources'] ?? []))
            <h2>Sumber Dana</h2>
            <p>{{ implode(', ', $activity->proposal_data['funding_sources']) }}</p>
        @endif
    @endif

    @if($reportPhoto)<h2>Gambar Report</h2><img class="photo" src="{{ $reportPhoto }}">@endif
    <h2>Senarai Kehadiran</h2>
    <table><tr><th>#</th><th>Nama</th><th>Emel</th><th>Masa Hadir</th></tr>
        @forelse($attendances as $index => $attendance)
            <tr><td>{{ $index + 1 }}</td><td>{{ $attendance->user->name }}</td><td>{{ $attendance->user->email }}</td><td>{{ $attendance->scanned_at->format('d/m/Y h:i A') }}</td></tr>
        @empty
            <tr><td colspan="4">Tiada rekod kehadiran.</td></tr>
        @endforelse
    </table>
</body>
</html>
