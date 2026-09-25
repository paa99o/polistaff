<!doctype html>
<html lang="ms"><head><meta charset="utf-8"><style>
@page { margin: 92px 58px 72px; }
body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 10.5px; line-height: 1.45; }
header { position: fixed; top: -72px; left: 0; right: 0; height: 58px; border-bottom: 1px solid #64748b; text-align: center; }
header img { height: 48px; position: absolute; left: 0; top: 2px; }
header div { padding-top: 13px; font-weight: bold; font-size: 11px; }
footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #94a3b8; padding-top: 7px; font-size: 9px; color: #475569; }
.page-number:after { content: counter(page); }
h1 { text-align: center; font-size: 17px; margin: 8px 0 4px; }
.subtitle { text-align: center; font-size: 14px; font-weight: bold; margin: 0 0 26px; }
h2 { font-size: 12px; margin: 18px 0 7px; }
table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
th, td { border: 1px solid #64748b; padding: 6px; text-align: left; vertical-align: top; }
th { background: #e2e8f0; }
.meta td:first-child { width: 28%; font-weight: bold; }
.text { white-space: pre-wrap; }
.signatures { margin-top: 42px; width: 100%; }
.signatures td { border: 0; text-align: center; width: 33%; padding-top: 45px; }
</style></head><body>
<header>@if($logo)<img src="{{ $logo }}" alt="Logo POLIBEST">@endif<div>POLIBEST · PORTAL PENGURUSAN KELAB STAF</div></header>
<footer>Kertas Kerja Program · Aktiviti #{{ $activity->id }} · Templat {{ $version->template_version }} · Versi {{ $version->version }}<span style="float:right">Muka surat <span class="page-number"></span></span></footer>
<h1>KERTAS KERJA PROGRAM</h1><div class="subtitle">{{ $version->content['title'] }}</div>
<h2>1.0 MAKLUMAT PROGRAM</h2><table class="meta"><tr><td>Jenis Program</td><td>{{ $version->content['activity_type'] ?: '-' }}</td></tr><tr><td>Kategori Program</td><td>{{ $version->content['program_category'] ?: '-' }}</td></tr><tr><td>Penganjur / Unit</td><td>{{ $version->content['organizing_unit'] ?: '-' }}</td></tr><tr><td>Tarikh Program</td><td>{{ $version->content['start_date'] ?: '-' }}{{ ($version->content['end_date'] ?? null) && $version->content['end_date'] !== $version->content['start_date'] ? ' hingga '.$version->content['end_date'] : '' }}</td></tr><tr><td>Masa Program</td><td>{{ $version->content['start_time'] ?: '-' }} – {{ $version->content['end_time'] ?: '-' }}</td></tr><tr><td>Tempat Program</td><td>{{ $version->content['location'] ?: '-' }}</td></tr><tr><td>Mod Pelaksanaan</td><td>{{ $version->content['implementation_mode'] ?: '-' }}</td></tr></table>
<h2>2.0 LATAR BELAKANG PROGRAM</h2><div class="text">{{ $version->content['background'] ?: '-' }}</div>
<h2>3.0 OBJEKTIF PROGRAM</h2><ol>@forelse($version->content['objectives'] ?? [] as $objective)<li>{{ $objective }}</li>@empty<li>-</li>@endforelse</ol>
<h2>4.0 SASARAN PESERTA</h2><p>Kumpulan sasaran: {{ implode(', ', $version->content['target_participants'] ?? []) ?: '-' }}<br>Bilangan peserta: {{ $version->content['expected_participants'] ?? '-' }}<br>Kriteria: {{ $version->content['participant_criteria'] ?: '-' }}</p>
<h2>5.0 TENTATIF PROGRAM</h2><table><tr><th style="width:25%">Masa</th><th>Aktiviti</th></tr>@forelse($version->content['tentative'] ?? [] as $row)<tr><td>{{ $row['time'] ?? '-' }}</td><td>{{ $row['description'] ?? '-' }}</td></tr>@empty<tr><td colspan="2">-</td></tr>@endforelse</table>
<h2>6.0 JAWATANKUASA PROGRAM</h2><table><tr><th style="width:10%">Bil</th><th>Nama</th><th>Jawatan</th></tr>@forelse($version->content['committee'] ?? [] as $i => $row)<tr><td>{{ $i + 1 }}</td><td>{{ $row['name'] ?? '-' }}</td><td>{{ $row['position'] ?? '-' }}</td></tr>@empty<tr><td colspan="3">-</td></tr>@endforelse</table>
<h2>7.0 ANGGARAN PERBELANJAAN</h2><table><tr><th>Bil</th><th>Perkara</th><th>Kuantiti</th><th>Kos Seunit</th><th>Jumlah</th></tr>@php($total = 0)@forelse($version->content['budget_items'] ?? [] as $i => $row)@php($line = (float)($row['quantity'] ?? 0) * (float)($row['estimated_cost'] ?? 0))@php($total += $line)<tr><td>{{ $i + 1 }}</td><td>{{ $row['description'] ?? '-' }}</td><td>{{ $row['quantity'] ?? 0 }}</td><td>RM {{ number_format((float)($row['estimated_cost'] ?? 0), 2) }}</td><td>RM {{ number_format($line, 2) }}</td></tr>@empty<tr><td colspan="5">-</td></tr>@endforelse<tr><th colspan="4">Jumlah Perbelanjaan</th><th>RM {{ number_format($total, 2) }}</th></tr></table>
<h2>8.0 SUMBER KEWANGAN</h2><p>{{ implode(', ', $version->content['funding_sources'] ?? []) ?: '-' }}</p>
<h2>9.0 PENUTUP</h2><div class="text">{{ $version->content['closing'] }}</div>
<h2>10.0 PENGESAHAN / KELULUSAN</h2><table class="signatures"><tr><td>Disediakan oleh:<br><br><br><strong>{{ $version->content['prepared_by'] ?? '-' }}</strong></td><td>Disemak oleh:<br><br><br><strong>{{ $version->content['checked_by'] ?? '-' }}</strong></td><td>Diluluskan oleh:<br><br><br><strong>{{ $version->content['approved_by'] ?? '-' }}</strong></td></tr></table>
<script type="text/php">if (isset($pdf)) { $font = $fontMetrics->get_font('DejaVu Sans', 'normal'); $pdf->page_text(520, 815, 'Muka surat {PAGE_NUM}', $font, 8, array(71, 85, 105)); }</script>
</body></html>
