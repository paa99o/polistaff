<?php

namespace App\Http\Controllers;

use App\Models\MemberDocument;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MemberDocumentController extends Controller
{
    public function index(Request $request): View
    {
        return view('documents.index', ['documents' => $request->user()->documents()->latest()->paginate(10)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'document_type' => ['required', 'string', 'max:120'], 'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096']]);
        $document = MemberDocument::create(['user_id' => $request->user()->id, 'title' => $data['title'], 'document_type' => $data['document_type'], 'file_path' => $request->file('document')->store('member-documents', 'private')]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'uploaded', 'module' => 'Member Document', 'record_type' => MemberDocument::class, 'record_id' => $document->id, 'description' => 'Uploaded document '.$document->title.'.', 'changes' => $document->only(['title', 'document_type', 'file_path']), 'ip_address' => $request->ip()]);

        return back()->with('status', 'Dokumen dimuat naik.');
    }

    public function show(MemberDocument $document)
    {
        abort_unless($document->user_id === auth()->id() || auth()->user()->hasRole('admin'), 403);
        abort_unless(Storage::disk('private')->exists($document->file_path), 404);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'downloaded', 'module' => 'Member Document', 'record_type' => MemberDocument::class, 'record_id' => $document->id, 'description' => 'Downloaded document '.$document->title.'.', 'changes' => ['file_path' => $document->file_path], 'ip_address' => request()->ip()]);

        return Storage::disk('private')->response($document->file_path);
    }

    public function destroy(MemberDocument $document): RedirectResponse
    {
        abort_unless($document->user_id === auth()->id() || auth()->user()->hasRole('admin'), 403);
        Storage::disk('private')->delete($document->file_path);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'deleted', 'module' => 'Member Document', 'record_type' => MemberDocument::class, 'record_id' => $document->id, 'description' => 'Deleted document '.$document->title.'.', 'changes' => $document->only(['title', 'document_type', 'file_path']), 'ip_address' => request()->ip()]);
        $document->delete();

        return back()->with('status', 'Dokumen dipadam.');
    }
}
