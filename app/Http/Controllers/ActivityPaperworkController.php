<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityPaperworkVersion;
use App\Models\User;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ActivityPaperworkController extends Controller
{
    private const TEMPLATE_VERSION = '3.0';

    public function generate(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorizeActivity($activity, $request->user());
        abort_if(in_array($activity->status, ['draft', 'cancelled'], true), 422, 'Kertas kerja hanya boleh dijana selepas aktiviti dihantar.');

        $version = $this->newVersion($activity, $request->user()->id, $this->fromActivity($activity));

        return redirect()->route('activities.paperwork.preview', [$activity, $version])->with('status', 'Draf kertas kerja berjaya dijana.');
    }

    public function preview(Request $request, Activity $activity, ActivityPaperworkVersion $version): View
    {
        $this->authorizeActivity($activity, $request->user());
        abort_unless($version->activity_id === $activity->id, 404);

        return view('activities.paperwork-preview', [
            'activity' => $activity,
            'version' => $version,
            'versions' => $activity->paperworkVersions()->with('generator')->get(),
            'missingInformation' => $this->missingInformation($activity),
        ]);
    }

    public function save(Request $request, Activity $activity, ActivityPaperworkVersion $version): RedirectResponse
    {
        $this->authorizeActivity($activity, $request->user());
        abort_unless($version->activity_id === $activity->id, 404);
        $data = $request->validate([
            'summary' => ['required', 'string', 'max:10000'],
            'impact' => ['required', 'string', 'max:10000'],
            'closing' => ['required', 'string', 'max:4000'],
            'objectives_text' => ['nullable', 'string', 'max:5000'],
        ]);

        $content = $version->content;
        $content['background'] = $data['summary'];
        $content['summary'] = $data['summary'];
        $content['impact'] = $data['impact'];
        $content['closing'] = $data['closing'];
        $content['objectives'] = collect(preg_split('/\r\n|\r|\n/', $data['objectives_text'] ?? ''))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
        $saved = $this->newVersion($activity, $request->user()->id, $content);

        return redirect()->route('activities.paperwork.preview', [$activity, $saved])->with('status', 'Kertas kerja disimpan sebagai versi '.$saved->version.'.');
    }

    public function download(Request $request, Activity $activity, ActivityPaperworkVersion $version): Response
    {
        $this->authorizeActivity($activity, $request->user());
        abort_unless($version->activity_id === $activity->id, 404);
        $isPreview = $request->boolean('render');
        abort_if(! $isPreview && $activity->status !== 'approved', 422, 'Dokumen akhir tersedia selepas aktiviti diluluskan.');

        $logoPath = public_path('images/polibest-logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        $pdf = new Dompdf;
        $pdf->loadHtml(view('activities.paperwork-pdf', compact('activity', 'version', 'logo'))->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($isPreview ? 'inline' : 'attachment').'; filename="kertas-kerja-'.Str::slug($activity->title).'-v'.$version->version.'.pdf"',
        ]);
    }

    private function authorizeActivity(Activity $activity, User $user): void
    {
        abort_unless($activity->created_by === $user->id || $user->hasRole('admin', 'treasurer'), 403);
    }

    private function newVersion(Activity $activity, int $userId, array $content): ActivityPaperworkVersion
    {
        return $activity->paperworkVersions()->create([
            'generated_by' => $userId,
            'version' => ((int) $activity->paperworkVersions()->max('version')) + 1,
            'template_version' => self::TEMPLATE_VERSION,
            'status' => $activity->status === 'approved' ? 'final' : 'draft',
            'content' => $content,
            'generated_at' => now(),
        ]);
    }

    private function fromActivity(Activity $activity): array
    {
        $creator = $activity->creator;
        $verifier = $activity->treasurerVerifier;
        $approver = $activity->reviewed_by ? User::find($activity->reviewed_by) : null;
        $proposal = $activity->proposal_data ?? [];

        return [
            'title' => $activity->title,
            'organizing_unit' => $activity->organizing_unit,
            'person_in_charge' => $activity->person_in_charge,
            'program_level' => $proposal['program_level'] ?? '',
            'purposes' => $proposal['purposes'] ?? [],
            'course_categories' => $proposal['course_categories'] ?? [],
            'summary' => $proposal['summary'] ?? '',
            'impact' => $proposal['impact'] ?? '',
            'session' => $proposal['session'] ?? '',
            'start_date' => $activity->date_time?->format('d/m/Y'),
            'end_date' => ($activity->end_time ?? $activity->date_time)?->format('d/m/Y'),
            'start_time' => $activity->date_time?->format('h:i A'),
            'end_time' => $activity->end_time?->format('h:i A'),
            'location' => $activity->location,
            'background' => $proposal['summary'] ?? '',
            'objectives' => $proposal['objectives'] ?? [],
            'target_participants' => array_values(array_diff($proposal['target_participants'] ?? [], ['Student'])),
            'expected_participants' => $activity->expected_participants ?? $activity->max_participants,
            'tentative' => $proposal['tentative'] ?? [],
            'committee' => $proposal['committee'] ?? [],
            'speakers' => $proposal['speakers'] ?? [],
            'budget_items' => $proposal['budget_items'] ?? [],
            'funding_sources' => $proposal['funding_sources'] ?? [],
            'finance_source' => $proposal['finance_source'] ?? '',
            'kulpl_review' => $proposal['kulpl_review'] ?? '',
            'closing' => $proposal['closing'] ?? 'Adalah diharapkan pelaksanaan program ini dapat mencapai objektif yang telah ditetapkan serta memberi manfaat kepada semua peserta. Kerjasama dan sokongan semua pihak amat dihargai.',
            'prepared_by' => $creator?->name ?? '-',
            'checked_by' => $verifier?->name ?? 'Pegawai aktiviti',
            'approved_by' => $approver?->name ?? 'Untuk kelulusan',
            'activity_id' => $activity->id,
            'source_updated_at' => $activity->updated_at?->toIso8601String(),
        ];
    }

    private function missingInformation(Activity $activity): array
    {
        $proposal = $activity->proposal_data ?? [];
        $missing = [];
        $budgetItems = collect($proposal['budget_items'] ?? []);
        if (($proposal['finance_source'] ?? null) !== 'Tiada' && ($budgetItems->isEmpty() || $budgetItems->contains(fn ($row) => ! is_array($row) || blank($row['description'] ?? null) || ! is_numeric($row['quantity'] ?? null) || ! is_numeric($row['estimated_cost'] ?? null)))) $missing[] = 'Butiran anggaran perbelanjaan belum lengkap.';
        $committee = collect($proposal['committee'] ?? []);
        if ($committee->isEmpty() || $committee->contains(fn ($row) => ! is_array($row) || blank($row['name'] ?? null) || blank($row['position'] ?? null))) $missing[] = 'Maklumat jawatankuasa belum lengkap.';
        if (empty($proposal['objectives'] ?? [])) $missing[] = 'Objektif program belum diisi.';
        if (empty($proposal['program_level'] ?? null)) $missing[] = 'Peringkat program belum dipilih.';
        if (empty($proposal['purposes'] ?? [])) $missing[] = 'Penjajaran / tujuan program belum dipilih.';
        if (empty($proposal['summary'] ?? null)) $missing[] = 'Ringkasan program belum diisi.';
        if (empty($proposal['impact'] ?? null)) $missing[] = 'Hasil / impak program belum diisi.';
        if (empty($proposal['closing'] ?? null)) $missing[] = 'Penutup program belum diisi.';
        if (empty($proposal['finance_source'] ?? null)) $missing[] = 'Sumber kewangan belum dipilih.';
        if (empty($proposal['kulpl_review'] ?? null)) $missing[] = 'Status semakan KULPL belum dipilih.';
        if (! $activity->expected_participants) $missing[] = 'Bilangan peserta belum dinyatakan.';
        $targetParticipants = array_diff($proposal['target_participants'] ?? [], ['Student']);
        if (empty($targetParticipants)) $missing[] = 'Kumpulan sasaran belum dipilih.';
        if (! $activity->location) $missing[] = 'Tempat program belum dinyatakan.';

        return $missing;
    }
}
