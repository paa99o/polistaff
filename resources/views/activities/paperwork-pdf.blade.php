<!doctype html>
<html lang="ms"><head><meta charset="utf-8"><style>
@page { margin: 42px 48px 58px; }
body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10.5px; line-height: 1.45; }
header { position: fixed; top: -28px; left: 0; right: 0; height: 25px; text-align: right; color: #444; font-size: 8px; }
footer { position: fixed; bottom: -38px; left: 0; right: 0; border-top: .6px solid #999; padding-top: 5px; font-size: 8px; color: #444; }
.page-number:after { content: counter(page); }
.cover { min-height: 690px; text-align: center; page-break-after: always; }
.logo { max-height: 90px; max-width: 180px; margin: 24px auto 40px; }
.cover h1 { font-size: 23px; letter-spacing: 1px; margin: 0 0 15px; }
.cover .program-title { font-weight: bold; font-size: 17px; text-transform: uppercase; margin: 0 auto 20px; max-width: 530px; }
.cover .session { font-size: 14px; margin-bottom: 38px; }
.cover-meta { width: 82%; margin: 0 auto; border-collapse: collapse; }
.cover-meta td { border: 0; padding: 7px; text-align: center; }
.cover-meta .label { font-weight: bold; letter-spacing: .4px; padding-top: 11px; }
.cover .prepared { margin-top: 28px; font-weight: bold; }
h2 { font-size: 12px; margin: 17px 0 7px; text-transform: uppercase; page-break-after: avoid; }
h3 { font-size: 10.5px; margin: 10px 0 4px; page-break-after: avoid; }
p { margin: 4px 0 8px; text-align: justify; }
table { width: 100%; border-collapse: collapse; margin: 6px 0 10px; }
th, td { border: .6px solid #555; padding: 5px 6px; text-align: left; vertical-align: top; }
th { background: #eee; }
.plain td { border: 0; padding: 3px 4px; }
.meta td:first-child { width: 24%; font-weight: bold; }
.categories td { width: 50%; }
.mark { font-family: DejaVu Sans; font-weight: bold; }
.text { white-space: pre-wrap; text-align: justify; }
.signature-table { margin-top: 18px; }
.signature-table td { height: 112px; text-align: center; vertical-align: bottom; width: 33%; }
.signature-line { border-top: .6px solid #333; padding-top: 5px; }
.note { font-size: 9px; }
tr { page-break-inside: avoid; }
</style></head><body>
<header>POLITEKNIK BESUT TERENGGANU · KERTAS KERJA PROGRAM</header>
<footer>Kertas Kerja Program · Aktiviti #{{ $activity->id }} · Templat {{ $version->template_version }} · Versi {{ $version->version }}<span style="float:right">Muka surat <span class="page-number"></span></span></footer>
<section class="cover">
    @if($logo)<img class="logo" src="{{ $logo }}" alt="Logo Politeknik Besut Terengganu">@endif
    <h1>KERTAS KERJA</h1>
    <div class="program-title">{{ $version->content['title'] ?? $activity->title }}</div>
    @if($version->content['session'] ?? null)<div class="session">{{ $version->content['session'] }}</div>@endif
    <table class="cover-meta"><tr><td class="label">TARIKH</td></tr><tr><td>{{ $version->content['start_date'] ?? '-' }}{{ !empty($version->content['end_date']) && ($version->content['end_date'] ?? null) !== ($version->content['start_date'] ?? null) ? ' hingga '.$version->content['end_date'] : '' }}</td></tr>
    <tr><td class="label">TEMPAT</td></tr><tr><td>{{ $version->content['location'] ?? '-' }}</td></tr>
    <tr><td class="label">ANJURAN</td></tr><tr><td>{{ $version->content['organizing_unit'] ?? '-' }}</td></tr>
    <tr><td class="label">DISEDIAKAN OLEH</td></tr><tr><td>{{ $version->content['prepared_by'] ?? '-' }}</td></tr></table>
</section>

<h2>Kategori Kursus</h2>
@php($courseCategories = ['Kepimpinan', 'Kewangan', 'Lain-lain', 'Pembangunan & Penyelidikan', 'Pembangunan Diri', 'Pengajaran & Pembelajaran', 'Pentadbiran / Pengurusan', 'Teknikal', 'Teknologi Maklumat', 'Perkeranian'])
<table class="categories"><tr><th>Bil.</th><th>Bidang Kursus</th><th>Pilihan</th><th>Bil.</th><th>Bidang Kursus</th><th>Pilihan</th></tr>
@for($i = 0; $i < 5; $i++)<tr><td>{{ sprintf('%02d', $i + 1) }}</td><td>{{ $courseCategories[$i] }}</td><td class="mark">{{ in_array($courseCategories[$i], $version->content['course_categories'] ?? [], true) ? '✓' : '' }}</td><td>{{ sprintf('%02d', $i + 6) }}</td><td>{{ $courseCategories[$i + 5] }}</td><td class="mark">{{ in_array($courseCategories[$i + 5], $version->content['course_categories'] ?? [], true) ? '✓' : '' }}</td></tr>@endfor
</table>
<div class="note"><strong>Penjajaran:</strong> @forelse($version->content['purposes'] ?? [] as $purpose){{ $purpose }}@if(!$loop->last), @endif @empty - @endforelse</div>

<h2>Maklumat Program / Kursus</h2>
<table class="meta"><tr><td>Nama Program</td><td>{{ $version->content['title'] ?? $activity->title }}</td></tr><tr><td>Peringkat / Sesi</td><td>{{ $version->content['program_level'] ?? '-' }}{{ !empty($version->content['session']) ? ' · '.$version->content['session'] : '' }}</td></tr><tr><td>Kategori Kursus</td><td>{{ implode(', ', $version->content['course_categories'] ?? []) ?: '-' }}</td></tr><tr><td>Ringkasan Program</td><td class="text">{{ ($version->content['summary'] ?? '') ?: (($version->content['background'] ?? '') ?: '-') }}</td></tr><tr><td>Objektif</td><td><ol style="margin:0;padding-left:18px">@forelse($version->content['objectives'] ?? [] as $objective)<li>{{ $objective }}</li>@empty<li>-</li>@endforelse</ol></td></tr><tr><td>Tempat</td><td>{{ $version->content['location'] ?? '-' }}</td></tr><tr><td>Tarikh / Masa</td><td>{{ $version->content['start_date'] ?? '-' }}{{ !empty($version->content['end_date']) && ($version->content['end_date'] ?? null) !== ($version->content['start_date'] ?? null) ? ' hingga '.$version->content['end_date'] : '' }} · {{ $version->content['start_time'] ?? '-' }} - {{ $version->content['end_time'] ?? '-' }}</td></tr><tr><td>Anjuran</td><td>{{ $version->content['organizing_unit'] ?? '-' }}</td></tr><tr><td>Kumpulan Sasaran</td><td>{{ implode(', ', $version->content['target_participants'] ?? []) ?: '-' }}{{ !empty($version->content['participant_criteria']) ? ' — '.$version->content['participant_criteria'] : '' }}</td></tr><tr><td>Bilangan Peserta</td><td>{{ $version->content['expected_participants'] ?? '-' }} orang</td></tr></table>

<h2>3. Hasil / Impak Program</h2><div class="text">{{ $version->content['impact'] ?? '-' }}</div>
<h2>4. Jawatankuasa Program</h2><div class="note">(Sila sertakan lampiran sekiranya perlu)</div><table><tr><th style="width:9%">Bil.</th><th>Nama</th><th>Jawatan / Peranan</th></tr>
@forelse($version->content['committee'] ?? [] as $i => $row)<tr><td>{{ $i + 1 }}</td><td>{{ $row['name'] ?? '-' }}</td><td>{{ $row['position'] ?? '-' }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</table>
<h2>5. Butiran Penceramah / Jemputan Luar / Perasmi Program</h2><table><tr><th>Nama Pegawai</th><th>Jawatan</th><th>Gred</th><th>Jabatan / Institusi</th></tr>
@forelse($version->content['speakers'] ?? [] as $speaker)<tr><td>{{ $speaker['name'] ?? '-' }}</td><td>{{ $speaker['position'] ?? '-' }}</td><td>{{ $speaker['grade'] ?? '-' }}</td><td>{{ $speaker['institution'] ?? '-' }}</td></tr>@empty<tr><td colspan="4">Tiada jemputan luar dinyatakan.</td></tr>@endforelse</table>
<h2>6. Atur Cara Program</h2><table><tr><th style="width:23%">Hari / Tarikh</th><th style="width:18%">Waktu</th><th>Aktiviti</th></tr>
@forelse($version->content['tentative'] ?? [] as $row)<tr><td>{{ !empty($row['date']) ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : ($version->content['start_date'] ?? '-') }}</td><td>{{ $row['time'] ?? '-' }}</td><td>{{ $row['description'] ?? '-' }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</table>
<h2>7. Sumber Kewangan</h2><p>@php($financeSource = $version->content['finance_source'] ?? '')@foreach(['Kerajaan', 'Tiada', 'Akaun Amanah'] as $source)<span class="mark">{{ $financeSource === $source ? '☒' : '☐' }}</span> {{ $source }} &nbsp;&nbsp; @endforeach</p>
<h2>8. Peruntukan Kewangan</h2><table><tr><th style="width:7%">Bil.</th><th>Perkara</th><th>Harga Seunit (RM)</th><th>Kuantiti</th><th>Jumlah (RM)</th><th>Sumber Kewangan</th></tr>
@php($total = 0)@forelse($version->content['budget_items'] ?? [] as $i => $row)@php($line = (float)($row['quantity'] ?? 0) * (float)($row['estimated_cost'] ?? 0))@php($total += $line)<tr><td>{{ $i + 1 }}</td><td>{{ $row['description'] ?? '-' }}</td><td>{{ number_format((float)($row['estimated_cost'] ?? 0), 2) }}</td><td>{{ $row['quantity'] ?? 0 }}</td><td>{{ number_format($line, 2) }}</td><td>{{ $row['source_code'] ?? '-' }}</td></tr>@empty<tr><td colspan="6">Tiada peruntukan kewangan dinyatakan.</td></tr>@endforelse
<tr><th colspan="4" style="text-align:right">Jumlah Keseluruhan</th><th>RM {{ number_format($total, 2) }}</th><td></td></tr></table>
<p class="note">Disahkan bahawa baki peruntukan di bawah pecahan kepala yang dinyatakan adalah mencukupi bagi menampung kos aktiviti ini.</p><p style="margin-top:25px">..........................................................<br>(Tandatangan Penolong Akauntan &amp; cap rasmi)<br>Tarikh: ................................</p>
<h2>9. Semakan KULPL</h2><p>Semakan KULPL bagi program PSH atau program latihan staf seperti kursus, taklimat dan lain-lain.</p><p><span class="mark">{{ ($version->content['kulpl_review'] ?? '') === 'Berkaitan' ? '☒' : '☐' }}</span> Berkaitan &nbsp;&nbsp; <span class="mark">{{ ($version->content['kulpl_review'] ?? '') === 'Tidak Berkaitan' ? '☒' : '☐' }}</span> Tidak Berkaitan</p><p style="margin-top:20px">Disemak oleh: ..........................................................<br>(NIK HAYATI BINTI NIK ABDULLAH)<br>Ketua Unit Latihan dan Pendidikan Lanjutan<br>Politeknik Besut Terengganu<br>Tarikh: ................................</p>
<h2>10. Penutup</h2><div class="text">{{ $version->content['closing'] ?? '-' }}</div>
<h2>11. Kelulusan Kertas Kerja</h2><table class="signature-table"><tr>
<td><div class="signature-line">Disediakan Oleh<br><strong>{{ $version->content['prepared_by'] ?? '-' }}</strong><br>Pengarah Program<br>Politeknik Besut Terengganu<br>Tarikh: ........................</div></td>
<td><div class="signature-line">Disemak Oleh<br><strong>{{ $version->content['checked_by'] ?? '-' }}</strong><br>Timbalan Pengarah (Akademik)<br>Politeknik Besut Terengganu<br>Tarikh: ........................</div></td>
<td><div class="signature-line">Diluluskan Oleh<br><strong>{{ $version->content['approved_by'] ?? '-' }}</strong><br>Pengarah<br>Politeknik Besut Terengganu<br>Tarikh: ........................</div></td>
</tr></table>
</body></html>
