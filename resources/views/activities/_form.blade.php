<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="title">Nama aktiviti</label>
        <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $activity->title ?? '') }}" required>
        @include('partials.errors', ['name' => 'title'])
    </div>
    @unless($isCreate ?? false)<div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
            @foreach(['draft','pending_approval','approved','cancelled'] as $status)
                <option value="{{ $status }}" @selected(old('status', $activity->status ?? 'draft') === $status)>{{ \App\Support\PolistaffLabels::status($status) }}</option>
            @endforeach
        </select>
        @include('partials.errors', ['name' => 'status'])
    </div>@endunless
    @if($isCreate ?? false)
    <div class="col-md-4"><label class="form-label" for="activity_date">Tarikh aktiviti</label><input class="form-control @error('activity_date') is-invalid @enderror" id="activity_date" type="date" name="activity_date" value="{{ old('activity_date') }}" required>@include('partials.errors', ['name' => 'activity_date'])</div>
    <div class="col-md-4"><label class="form-label" for="start_time">Masa bermula</label><input class="form-control @error('start_time') is-invalid @enderror" id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" required>@include('partials.errors', ['name' => 'start_time'])</div>
    <div class="col-md-4"><label class="form-label" for="end_time">Masa berakhir</label><input class="form-control @error('end_time') is-invalid @enderror" id="end_time" type="time" name="end_time" value="{{ old('end_time') }}" required>@include('partials.errors', ['name' => 'end_time'])</div>
    @else
    <div class="col-md-6">
        <label class="form-label" for="date_time">Tarikh & Masa</label>
        <input class="form-control @error('date_time') is-invalid @enderror" id="date_time" type="datetime-local" name="date_time" value="{{ old('date_time', isset($activity) ? $activity->date_time->format('Y-m-d\TH:i') : '') }}" required>
        @include('partials.errors', ['name' => 'date_time'])
    </div>
    @endif
    <div class="col-md-4">
        <label class="form-label" for="location">Lokasi</label>
        <input class="form-control @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location', $activity->location ?? '') }}" required>
        @include('partials.errors', ['name' => 'location'])
    </div>
    @unless($isCreate ?? false)<div class="col-md-2">
        <label class="form-label" for="max_participants">Maksimum</label>
        <input class="form-control @error('max_participants') is-invalid @enderror" id="max_participants" type="number" name="max_participants" value="{{ old('max_participants', $activity->max_participants ?? '') }}">
        @include('partials.errors', ['name' => 'max_participants'])
    </div>@endunless
    <div class="col-md-6">
        <label class="form-label" for="registration_opens_at">Pendaftaran Dibuka</label>
        <input class="form-control @error('registration_opens_at') is-invalid @enderror" id="registration_opens_at" type="datetime-local" name="registration_opens_at" value="{{ old('registration_opens_at', isset($activity) && $activity->registration_opens_at ? $activity->registration_opens_at->format('Y-m-d\TH:i') : '') }}">
        @include('partials.errors', ['name' => 'registration_opens_at'])
    </div>
    <div class="col-md-6">
        <label class="form-label" for="registration_closes_at">Pendaftaran Ditutup</label>
        <input class="form-control @error('registration_closes_at') is-invalid @enderror" id="registration_closes_at" type="datetime-local" name="registration_closes_at" value="{{ old('registration_closes_at', isset($activity) && $activity->registration_closes_at ? $activity->registration_closes_at->format('Y-m-d\TH:i') : '') }}">
        @include('partials.errors', ['name' => 'registration_closes_at'])
    </div>
</div>
@unless($isCreate ?? false)<input type="hidden" name="end_time" value="{{ old('end_time', isset($activity) && $activity->end_time ? $activity->end_time->format('H:i') : '23:59') }}"><input type="hidden" name="status" value="{{ old('status', $activity->status ?? 'approved') }}">@endunless
<button class="btn btn-danger mt-3">{{ ($isCreate ?? false) ? 'Hantar Permohonan' : 'Simpan' }}</button>
