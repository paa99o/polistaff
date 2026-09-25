@extends('layouts.app', ['title' => 'Mohon Sumbangan'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 soft-panel-title">Mohon Sumbangan</h1>
                <p class="text-muted">Amaun tidak ditetapkan pada peringkat permohonan. Bendahari akan menetapkan had berdasarkan bajet kelab, kemudian admin menentukan amaun akhir. Lampirkan kertas kerja yang telah diluluskan sebagai rujukan.</p>
                <form method="post" action="{{ route('donations.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="category">Jenis Sumbangan</label>
                            <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" type="text" value="{{ old('category') }}" maxlength="150" placeholder="Nyatakan jenis sumbangan" required>
                            @include('partials.errors', ['name' => 'category'])
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Keterangan / sebab</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Terangkan tujuan permohonan sumbangan">{{ old('description') }}</textarea>
                            @include('partials.errors', ['name' => 'description'])
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="approved_paperwork">Kertas Kerja Diluluskan (PDF) <span class="text-danger">*</span></label>
                            <input class="form-control @error('approved_paperwork') is-invalid @enderror" id="approved_paperwork" name="approved_paperwork" type="file" accept="application/pdf,.pdf" required>
                            <div class="form-text">Muat naik salinan kertas kerja yang telah diluluskan. Format PDF sahaja, maksimum 10 MB.</div>
                            @include('partials.errors', ['name' => 'approved_paperwork'])
                        </div>
                    </div>
                    <button class="btn btn-danger mt-3" type="submit">Mohon Sumbangan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
