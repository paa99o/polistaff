@extends('layouts.app', ['title' => 'Pratonton '.$title])

@section('content')
<section class="report-download-preview">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div>
            <p class="stat-label mb-1">Semakan sebelum muat turun</p>
            <h1 class="h3 mb-1">{{ $title }}</h1>
            <p class="text-muted mb-0">Pastikan kandungan laporan ini betul sebelum memuat turun fail.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ $backUrl }}">Kembali</a>
            @if($inlineUrl)
                <a class="btn btn-outline-secondary" href="{{ $inlineUrl }}" target="_blank" rel="noopener">Buka / Cetak</a>
            @else
                <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Cetak Pratonton</button>
            @endif
            <a class="btn btn-danger" href="{{ $downloadUrl }}">Muat Turun {{ strtoupper($format) }}</a>
        </div>
    </div>

    <div class="alert alert-info no-print">
        Fail belum dimuat turun. Semak nama, tarikh, jumlah dan rekod yang dipaparkan terlebih dahulu.
    </div>

    @if($inlineUrl)
        <div class="card overflow-hidden">
            <iframe
                class="w-100 border-0"
                style="min-height: 75vh"
                src="{{ $inlineUrl }}"
                title="Pratonton {{ $title }}"
            ></iframe>
        </div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table mobile-records mb-0">
                    <thead>
                        <tr>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                @foreach($row as $index => $value)
                                    <td data-label="{{ $headers[$index] ?? '' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max(1, count($headers)) }}" class="text-muted">Tiada rekod untuk dipaparkan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>
@endsection
