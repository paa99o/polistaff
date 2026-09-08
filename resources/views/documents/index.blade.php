@extends('layouts.app', ['title' => 'Dokumen Ahli'])

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <p class="dashboard-kicker mb-1">Fail ahli</p>
                <h1 class="h4 soft-panel-title">Muat Naik Dokumen</h1>
                <form method="post" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="title">Tajuk</label>
                        <input class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="document_type">Jenis Dokumen</label>
                        <input class="form-control" id="document_type" name="document_type" placeholder="IC / Borang / Slip" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="document">Fail</label>
                        <input class="form-control" id="document" type="file" name="document" required>
                    </div>
                    <button class="btn btn-danger">
                        <i class="bi bi-cloud-arrow-up me-2" aria-hidden="true"></i>Muat Naik
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Dokumen Saya</h2>
                @forelse($documents as $document)
                    <div class="border-bottom py-3 d-flex justify-content-between gap-3">
                        <div>
                            <strong>{{ $document->title }}</strong>
                            <div class="small text-muted">
                                {{ $document->document_type }} &middot;
                                <a href="{{ route('documents.show', $document) }}" target="_blank">Lihat fail</a>
                            </div>
                        </div>
                        <form method="post" action="{{ route('documents.destroy', $document) }}" data-confirm="Padam dokumen ini? Tindakan ini tidak boleh dibatalkan.">
                            @csrf
                            @method('delete')
                            <button class="btn btn-sm btn-outline-secondary">Padam</button>
                        </form>
                    </div>
                @empty
                    <div class="dashboard-empty-state my-3">
                        <span class="stat-icon"><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                        <p class="text-muted mb-0">Belum ada dokumen dimuat naik.</p>
                    </div>
                @endforelse
            </div>
        </div>
        <div class="mt-3">{{ $documents->links() }}</div>
    </div>
</div>
@endsection
