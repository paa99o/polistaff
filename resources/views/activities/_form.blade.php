<div class="row g-3">
    <div class="col-md-8"><label class="form-label">Tajuk</label><input class="form-control" name="title" value="{{ old('title', $activity->title ?? '') }}" required></div>
    <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['draft','pending_approval','approved','cancelled'] as $status)<option value="{{ $status }}" @selected(old('status',$activity->status ?? 'draft')===$status)>{{ $status }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Tarikh & Masa</label><input class="form-control" type="datetime-local" name="date_time" value="{{ old('date_time', isset($activity) ? $activity->date_time->format('Y-m-d\TH:i') : '') }}" required></div>
    <div class="col-md-4"><label class="form-label">Lokasi</label><input class="form-control" name="location" value="{{ old('location', $activity->location ?? '') }}" required></div>
    <div class="col-md-2"><label class="form-label">Maksimum</label><input class="form-control" type="number" name="max_participants" value="{{ old('max_participants', $activity->max_participants ?? '') }}"></div>
    <div class="col-md-6"><label class="form-label">Registration Opens</label><input class="form-control" type="datetime-local" name="registration_opens_at" value="{{ old('registration_opens_at', isset($activity) && $activity->registration_opens_at ? $activity->registration_opens_at->format('Y-m-d\TH:i') : '') }}"></div>
    <div class="col-md-6"><label class="form-label">Registration Closes</label><input class="form-control" type="datetime-local" name="registration_closes_at" value="{{ old('registration_closes_at', isset($activity) && $activity->registration_closes_at ? $activity->registration_closes_at->format('Y-m-d\TH:i') : '') }}"></div>
    <div class="col-md-6"><label class="form-label">Attendance Opens</label><input class="form-control" type="datetime-local" name="attendance_opens_at" value="{{ old('attendance_opens_at', isset($activity) && $activity->attendance_opens_at ? $activity->attendance_opens_at->format('Y-m-d\TH:i') : '') }}"></div>
    <div class="col-md-6"><label class="form-label">Attendance Closes</label><input class="form-control" type="datetime-local" name="attendance_closes_at" value="{{ old('attendance_closes_at', isset($activity) && $activity->attendance_closes_at ? $activity->attendance_closes_at->format('Y-m-d\TH:i') : '') }}"></div>
    <div class="col-12"><label class="form-label">Penerangan</label><textarea class="form-control" name="description" rows="4">{{ old('description', $activity->description ?? '') }}</textarea></div>
    <div class="col-12">
        <label class="form-label" for="evidence_photo">Foto bukti aktiviti</label>
        <input class="form-control" id="evidence_photo" type="file" name="evidence_photo" accept="image/*">
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
