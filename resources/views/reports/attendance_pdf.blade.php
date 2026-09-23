<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { color: #334155; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { color: #1557d8; font-size: 20px; }
        p { margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #94a3b8; padding: 7px; text-align: left; }
        th { background: #e2e8f0; }
    </style>
</head>
<body>
    <h1>Laporan Kehadiran POLIBEST</h1>
    <p>Dijana pada {{ now()->format('d/m/Y h:i A') }}</p>
    <table>
        <thead><tr><th>#</th><th>Ahli</th><th>Aktiviti</th><th>Masa</th></tr></thead>
        <tbody>
            @forelse($attendances as $attendance)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->activity->title }}</td>
                    <td>{{ $attendance->scanned_at->format('d/m/Y h:i A') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tiada rekod kehadiran.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
