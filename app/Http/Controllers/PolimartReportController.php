<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PolimartItem;
use App\Models\PolimartReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolimartReportController extends Controller
{
    public function store(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        abort_unless($polimartItem->status === 'active' && $polimartItem->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'reason' => ['required', 'in:scam,prohibited,misleading,duplicate,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyReported = PolimartReport::where('polimart_item_id', $polimartItem->id)
            ->where('reporter_id', $request->user()->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyReported) {
            return back()->with('status', 'Report anda untuk listing ini sedang disemak oleh admin.');
        }

        PolimartReport::create([
            ...$data,
            'polimart_item_id' => $polimartItem->id,
            'reporter_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Laporan listing telah dihantar kepada admin.');
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        return view('admin.polimart-reports', [
            'status' => $status,
            'reports' => PolimartReport::with(['item', 'reporter', 'reviewer'])
                ->when($status === 'pending', fn ($query) => $query->where('status', 'pending'))
                ->when($status === 'resolved', fn ($query) => $query->whereIn('status', ['dismissed', 'hidden', 'removed']))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, PolimartReport $polimartReport): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:dismissed,hidden,removed']]);
        $polimartReport->update([...$data, 'reviewed_by' => $request->user()->id]);

        if ($data['status'] === 'hidden') {
            $polimartReport->item()->update(['status' => 'hidden']);
        }

        if ($data['status'] === 'removed') {
            $polimartReport->item()->delete();
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'reviewed',
            'module' => 'PoliMart Moderation',
            'record_type' => PolimartReport::class,
            'record_id' => $polimartReport->id,
            'description' => 'Reviewed PoliMart report #'.$polimartReport->id.'.',
            'changes' => $data,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Laporan PoliMart telah dikemas kini.');
    }
}
