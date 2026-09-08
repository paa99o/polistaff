<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="title">Tajuk</label>
        <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $activity->title ?? '') }}" required>
        @include('partials.errors', ['name' => 'title'])
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
            @foreach(['draft','pending_approval','approved','cancelled'] as $status)
                <option value="{{ $status }}" @selected(old('status', $activity->status ?? 'draft') === $status)>{{ \App\Support\PolistaffLabels::status($status) }}</option>
            @endforeach
        </select>
        @include('partials.errors', ['name' => 'status'])
    </div>
    <div class="col-md-6">
        <label class="form-label" for="date_time">Tarikh & Masa</label>
        <input class="form-control @error('date_time') is-invalid @enderror" id="date_time" type="datetime-local" name="date_time" value="{{ old('date_time', isset($activity) ? $activity->date_time->format('Y-m-d\TH:i') : '') }}" required>
        @include('partials.errors', ['name' => 'date_time'])
    </div>
    <div class="col-md-4">
        <label class="form-label" for="location">Lokasi</label>
        <input class="form-control @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location', $activity->location ?? '') }}" required>
        @include('partials.errors', ['name' => 'location'])
    </div>
    <div class="col-md-2">
        <label class="form-label" for="max_participants">Maksimum</label>
        <input class="form-control @error('max_participants') is-invalid @enderror" id="max_participants" type="number" name="max_participants" value="{{ old('max_participants', $activity->max_participants ?? '') }}">
        @include('partials.errors', ['name' => 'max_participants'])
    </div>
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
    <div class="col-md-6">
        <label class="form-label" for="attendance_opens_at">Kehadiran Dibuka</label>
        <input class="form-control @error('attendance_opens_at') is-invalid @enderror" id="attendance_opens_at" type="datetime-local" name="attendance_opens_at" value="{{ old('attendance_opens_at', isset($activity) && $activity->attendance_opens_at ? $activity->attendance_opens_at->format('Y-m-d\TH:i') : '') }}">
        @include('partials.errors', ['name' => 'attendance_opens_at'])
    </div>
    <div class="col-md-6">
        <label class="form-label" for="attendance_closes_at">Kehadiran Ditutup</label>
        <input class="form-control @error('attendance_closes_at') is-invalid @enderror" id="attendance_closes_at" type="datetime-local" name="attendance_closes_at" value="{{ old('attendance_closes_at', isset($activity) && $activity->attendance_closes_at ? $activity->attendance_closes_at->format('Y-m-d\TH:i') : '') }}">
        @include('partials.errors', ['name' => 'attendance_closes_at'])
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Penerangan</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $activity->description ?? '') }}</textarea>
        @include('partials.errors', ['name' => 'description'])
    </div>
    <div class="col-12">
        <label class="form-label" for="evidence_photo">Foto bukti aktiviti</label>
        <input class="form-control @error('evidence_photo') is-invalid @enderror" id="evidence_photo" type="file" name="evidence_photo" accept="image/*">
        @include('partials.errors', ['name' => 'evidence_photo'])
        <div class="mt-3 d-none" id="evidence-photo-preview-wrap">
            <img class="activity-evidence-preview" id="evidence-photo-preview" src="" alt="Pratonton foto bukti aktiviti">
        </div>
        @if(! empty($activity?->evidence_photo_path))
            <div class="mt-3">
                <img class="activity-evidence-preview" src="{{ Storage::url($activity->evidence_photo_path) }}" alt="Foto bukti aktiviti semasa">
            </div>
        @endif
    </div>
</div>
<button class="btn btn-danger mt-3">Simpan</button>

@pushOnce('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('evidence_photo');
        const preview = document.getElementById('evidence-photo-preview');
        const previewWrap = document.getElementById('evidence-photo-preview-wrap');

        if (!input || !preview || !previewWrap) {
            return;
        }

        input.addEventListener('change', () => {
            const [file] = input.files;

            if (!file) {
                preview.removeAttribute('src');
                previewWrap.classList.add('d-none');
                return;
            }

            preview.src = URL.createObjectURL(file);
            previewWrap.classList.remove('d-none');
        });
    });
</script>
@endPushOnce
