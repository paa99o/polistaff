@php
    $activity = $activity ?? null;
    $proposal = $activity->proposal_data ?? [];
    $startDateValue = old('start_date', isset($activity) ? $activity->date_time?->format('Y-m-d') : '');
    $startTimeValue = old('start_time', isset($activity) ? $activity->date_time?->format('H:i') : '');
    $endDateValue = old('end_date', isset($activity) ? ($activity->end_time ?? $activity->date_time)?->format('Y-m-d') : '');
    $endTimeValue = old('end_time', isset($activity) ? $activity->end_time?->format('H:i') : '');
    $objectives = old('objectives', $proposal['objectives'] ?? ['']);
    $participants = old('target_participants', $proposal['target_participants'] ?? []);
    $tentative = old('tentative', $proposal['tentative'] ?? [['time' => '', 'description' => '']]);
    $committee = old('committee', $proposal['committee'] ?? [['name' => '', 'position' => '']]);
    $budgetItems = old('budget_items', $proposal['budget_items'] ?? [['description' => '', 'quantity' => '', 'estimated_cost' => '']]);
    $fundingSources = old('funding_sources', $proposal['funding_sources'] ?? []);
    $activityTypes = ['Seminar', 'Workshop', 'Training', 'Competition', 'Meeting', 'Visit', 'Community Program', 'Others'];
    $participantTypes = ['Student', 'Staff', 'Lecturer', 'External Community', 'Others'];
    $fundingOptions = ['Department Allocation', 'Participant Fee', 'Sponsorship', 'Grant', 'Others'];
@endphp

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Sila betulkan maklumat berikut sebelum hantar:</strong>
        <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<ol class="activity-wizard-progress" aria-label="Kemajuan borang aktiviti">
    @foreach(['Program', 'Objektif', 'Jadual', 'Jawatankuasa & Bajet', 'Semak'] as $index => $label)
        <li><button type="button" class="activity-wizard-step {{ $index === 0 ? 'is-active' : '' }}" data-go-step="{{ $index }}"><span>{{ $index + 1 }}</span><small>{{ $label }}</small></button></li>
    @endforeach
</ol>

<form id="activity-registration-wizard" method="post" action="{{ isset($activity) ? route('activities.update', $activity) : route('activities.store') }}">
    @csrf
    @if(isset($activity)) @method('put') @endif
    <input type="hidden" name="wizard" value="1">

    <section class="activity-wizard-panel" data-wizard-panel="0" aria-labelledby="activity-step-1">
        <h2 class="h5 soft-panel-title" id="activity-step-1">Langkah 1: Maklumat Program</h2>
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label" for="title">Nama Aktiviti / Program</label><input class="form-control" id="title" name="title" value="{{ old('title', $activity->title ?? '') }}" data-required data-label="Nama Aktiviti / Program"><div class="wizard-field-error"></div></div>
            <div class="col-md-4"><label class="form-label" for="activity_type">Jenis Aktiviti</label><select class="form-select" id="activity_type" name="activity_type" data-required data-label="Jenis Aktiviti"><option value="">Pilih jenis</option>@foreach($activityTypes as $type)<option value="{{ $type }}" @selected(old('activity_type', $activity->activity_type ?? '') === $type)>{{ $type }}</option>@endforeach</select><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="program_category">Kategori Program</label><input class="form-control" id="program_category" name="program_category" value="{{ old('program_category', $activity->program_category ?? '') }}" data-required data-label="Kategori Program"><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="organizing_unit">Jabatan / Unit Penganjur</label><input class="form-control" id="organizing_unit" name="organizing_unit" value="{{ old('organizing_unit', $activity->organizing_unit ?? '') }}" data-required data-label="Jabatan / Unit Penganjur"><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="person_in_charge">Pegawai Bertanggungjawab</label><input class="form-control" id="person_in_charge" name="person_in_charge" value="{{ old('person_in_charge', $activity->person_in_charge ?? auth()->user()->name) }}" data-required data-label="Pegawai Bertanggungjawab"><div class="wizard-field-error"></div></div>
        </div>
    </section>

    <section class="activity-wizard-panel" data-wizard-panel="1" aria-labelledby="activity-step-2" hidden>
        <h2 class="h5 soft-panel-title" id="activity-step-2">Langkah 2: Objektif & Peserta</h2>
        <div class="mb-4">
            <label class="form-label">Objektif Program</label>
            <div id="objectivesRows" class="activity-wizard-repeat-list">
                @foreach($objectives as $objective)
                    <div class="activity-wizard-repeat-row objective-row"><input class="form-control" name="objectives[]" value="{{ $objective }}" placeholder="Nyatakan objektif program" data-required data-label="Objektif Program"><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang objektif"><i class="bi bi-trash" aria-hidden="true"></i></button><div class="wizard-field-error"></div></div>
                @endforeach
            </div>
            <button class="btn btn-sm btn-outline-danger mt-2" type="button" data-add-row="objectivesRows" data-template="objectiveTemplate"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Objektif</button>
        </div>
        <div class="mb-3">
            <span class="form-label d-block">Sasaran Peserta</span>
            <div class="d-flex flex-wrap gap-3">
                @foreach($participantTypes as $participant)
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="target_participants[]" value="{{ $participant }}" @checked(in_array($participant, $participants, true)) data-required-group="participants"><span class="form-check-label">{{ $participant }}</span></label>
                @endforeach
            </div><div class="wizard-group-error" data-group-error="participants"></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="expected_participants">Anggaran Bilangan Peserta</label><input class="form-control" id="expected_participants" type="number" name="expected_participants" min="1" max="100000" value="{{ old('expected_participants', $activity->expected_participants ?? $activity->max_participants ?? '') }}" data-required data-label="Anggaran Bilangan Peserta"><div class="wizard-field-error"></div></div>
            <div class="col-12"><label class="form-label" for="participant_criteria">Kriteria / Syarat Peserta</label><textarea class="form-control" id="participant_criteria" name="participant_criteria" rows="3" placeholder="Contoh: terbuka kepada 30 peserta terawal">{{ old('participant_criteria', $activity->participant_criteria ?? '') }}</textarea></div>
        </div>
    </section>

    <section class="activity-wizard-panel" data-wizard-panel="2" aria-labelledby="activity-step-3" hidden>
        <h2 class="h5 soft-panel-title" id="activity-step-3">Langkah 3: Tarikh, Tempat & Tentatif</h2>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="start_date">Tarikh Mula</label><input class="form-control" id="start_date" type="date" name="start_date" value="{{ $startDateValue }}" data-required data-label="Tarikh Mula"><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="end_date">Tarikh Tamat</label><input class="form-control" id="end_date" type="date" name="end_date" value="{{ $endDateValue }}" data-required data-label="Tarikh Tamat"><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="start_time">Masa Mula</label><input class="form-control" id="start_time" type="time" name="start_time" value="{{ $startTimeValue }}" data-required data-label="Masa Mula"><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="end_time">Masa Tamat</label><input class="form-control" id="end_time" type="time" name="end_time" value="{{ $endTimeValue }}" data-required data-label="Masa Tamat"><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="location">Tempat / Venue</label><input class="form-control" id="location" name="location" value="{{ old('location', isset($activity) && $activity->location !== 'Belum ditetapkan' ? $activity->location : '') }}" data-required data-label="Tempat / Venue"><div class="wizard-field-error"></div></div>
            <div class="col-md-6"><label class="form-label" for="implementation_mode">Mod Pelaksanaan</label><select class="form-select" id="implementation_mode" name="implementation_mode" data-required data-label="Mod Pelaksanaan"><option value="">Pilih mod</option>@foreach(['Physical' => 'Fizikal', 'Online' => 'Dalam Talian', 'Hybrid' => 'Hibrid'] as $value => $label)<option value="{{ $value }}" @selected(old('implementation_mode', $activity->implementation_mode ?? '') === $value)>{{ $label }}</option>@endforeach</select><div class="wizard-field-error"></div></div>
        </div>
        <details class="mt-3"><summary class="text-danger">Tetapan pendaftaran pilihan</summary><div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label" for="registration_opens_at">Pendaftaran Dibuka</label><input class="form-control" type="datetime-local" id="registration_opens_at" name="registration_opens_at" value="{{ old('registration_opens_at', $activity?->registration_opens_at?->format('Y-m-d\\TH:i')) }}"></div><div class="col-md-6"><label class="form-label" for="registration_closes_at">Pendaftaran Ditutup</label><input class="form-control" type="datetime-local" id="registration_closes_at" name="registration_closes_at" value="{{ old('registration_closes_at', $activity?->registration_closes_at?->format('Y-m-d\\TH:i')) }}"></div></div></details>
        <hr class="my-4">
        <h3 class="h6">Tentatif Program</h3>
        <div class="activity-wizard-table-wrap"><table class="table align-middle"><thead><tr><th>Masa</th><th>Keterangan Aktiviti</th><th></th></tr></thead><tbody id="tentativeRows">
            @foreach($tentative as $row)
                <tr class="tentative-row"><td><input class="form-control" type="time" data-field="time" value="{{ $row['time'] ?? '' }}" data-required data-label="Masa tentatif"><div class="wizard-field-error"></div></td><td><input class="form-control" data-field="description" value="{{ $row['description'] ?? '' }}" placeholder="Aktiviti" data-required data-label="Keterangan tentatif"><div class="wizard-field-error"></div></td><td><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang baris tentatif"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr>
            @endforeach
        </tbody></table></div>
        <button class="btn btn-sm btn-outline-danger" type="button" data-add-row="tentativeRows" data-template="tentativeTemplate"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Baris</button>
    </section>

    <section class="activity-wizard-panel" data-wizard-panel="3" aria-labelledby="activity-step-4" hidden>
        <h2 class="h5 soft-panel-title" id="activity-step-4">Langkah 4: Jawatankuasa & Kewangan</h2>
        <h3 class="h6">Jawatankuasa Program</h3>
        <div class="activity-wizard-table-wrap"><table class="table align-middle"><thead><tr><th>Nama</th><th>Jawatan / Peranan</th><th></th></tr></thead><tbody id="committeeRows">
            @foreach($committee as $row)
                <tr class="committee-row"><td><input class="form-control" data-field="name" value="{{ $row['name'] ?? '' }}" data-required data-label="Nama ahli jawatankuasa"><div class="wizard-field-error"></div></td><td><input class="form-control" data-field="position" value="{{ $row['position'] ?? '' }}" data-required data-label="Jawatan / Peranan"><div class="wizard-field-error"></div></td><td><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang ahli jawatankuasa"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr>
            @endforeach
        </tbody></table></div>
        <button class="btn btn-sm btn-outline-danger mb-4" type="button" data-add-row="committeeRows" data-template="committeeTemplate"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Ahli Jawatankuasa</button>

        <h3 class="h6">Anggaran Bajet</h3>
        <div class="activity-wizard-table-wrap"><table class="table align-middle"><thead><tr><th>Keterangan Item</th><th>Kuantiti</th><th>Anggaran Kos / Unit (RM)</th><th>Jumlah (RM)</th><th></th></tr></thead><tbody id="budgetRows">
            @foreach($budgetItems as $row)
                <tr class="budget-row"><td><input class="form-control" data-field="description" value="{{ $row['description'] ?? '' }}" placeholder="Contoh: makanan"><div class="wizard-field-error"></div></td><td><input class="form-control" type="number" min="0.01" step="0.01" data-field="quantity" value="{{ $row['quantity'] ?? '' }}" placeholder="1"><div class="wizard-field-error"></div></td><td><input class="form-control" type="number" min="0" step="0.01" data-field="estimated_cost" value="{{ $row['estimated_cost'] ?? '' }}" placeholder="0.00"><div class="wizard-field-error"></div></td><td class="budget-row-total">RM 0.00</td><td><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang item bajet"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr>
            @endforeach
        </tbody><tfoot><tr><th colspan="3" class="text-end">Jumlah Anggaran</th><th id="budgetGrandTotal">RM 0.00</th><th></th></tr></tfoot></table></div>
        <button class="btn btn-sm btn-outline-danger mb-4" type="button" data-add-row="budgetRows" data-template="budgetTemplate"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Item Bajet</button>

        <div><span class="form-label d-block">Sumber Dana</span><div class="d-flex flex-wrap gap-3">
            @foreach($fundingOptions as $funding)
                <label class="form-check"><input class="form-check-input" type="checkbox" name="funding_sources[]" value="{{ $funding }}" @checked(in_array($funding, $fundingSources, true)) data-required-group="funding"><span class="form-check-label">{{ $funding }}</span></label>
            @endforeach
        </div><div class="wizard-group-error" data-group-error="funding"></div></div>
    </section>

    <section class="activity-wizard-panel" data-wizard-panel="4" aria-labelledby="activity-step-5" hidden>
        <h2 class="h5 soft-panel-title" id="activity-step-5">Langkah 5: Semak & Hantar</h2>
        <p class="text-muted">Semak ringkasan sebelum menghantar permohonan. Pilih nama langkah di atas untuk kembali dan mengubah maklumat.</p>
        <div id="activityWizardMissing" class="alert alert-warning" hidden><strong>Maklumat belum lengkap:</strong><ul class="mb-0 mt-2"></ul></div>
        <div class="activity-wizard-review" id="activityWizardSummary"></div>
    </section>

    <div class="activity-wizard-actions">
        <button class="btn btn-outline-secondary" type="button" id="activityWizardPrevious" hidden>Kembali</button>
        <button class="btn btn-outline-danger" type="submit" name="intent" value="draft" formnovalidate>Simpan Draf & Keluar</button>
        <span class="flex-grow-1"></span>
        <button class="btn btn-danger" type="button" id="activityWizardNext">Seterusnya</button>
        <button class="btn btn-danger" type="submit" id="activityWizardSubmit" name="intent" value="submit" hidden>Hantar Permohonan</button>
    </div>
</form>

<template id="objectiveTemplate"><div class="activity-wizard-repeat-row objective-row"><input class="form-control" name="objectives[]" placeholder="Nyatakan objektif program" data-required data-label="Objektif Program"><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang objektif"><i class="bi bi-trash" aria-hidden="true"></i></button><div class="wizard-field-error"></div></div></template>
<template id="tentativeTemplate"><tr class="tentative-row"><td><input class="form-control" type="time" data-field="time" data-required data-label="Masa tentatif"><div class="wizard-field-error"></div></td><td><input class="form-control" data-field="description" placeholder="Aktiviti" data-required data-label="Keterangan tentatif"><div class="wizard-field-error"></div></td><td><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang baris tentatif"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr></template>
<template id="committeeTemplate"><tr class="committee-row"><td><input class="form-control" data-field="name" data-required data-label="Nama ahli jawatankuasa"><div class="wizard-field-error"></div></td><td><input class="form-control" data-field="position" data-required data-label="Jawatan / Peranan"><div class="wizard-field-error"></div></td><td><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang ahli jawatankuasa"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr></template>
<template id="budgetTemplate"><tr class="budget-row"><td><input class="form-control" data-field="description" placeholder="Contoh: makanan"><div class="wizard-field-error"></div></td><td><input class="form-control" type="number" min="0.01" step="0.01" data-field="quantity" placeholder="1"><div class="wizard-field-error"></div></td><td><input class="form-control" type="number" min="0" step="0.01" data-field="estimated_cost" placeholder="0.00"><div class="wizard-field-error"></div></td><td class="budget-row-total">RM 0.00</td><td><button type="button" class="btn btn-outline-secondary" data-remove-row aria-label="Buang item bajet"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr></template>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('activity-registration-wizard');
    if (!form) return;
    const panels = [...form.querySelectorAll('[data-wizard-panel]')];
    const progress = [...document.querySelectorAll('[data-go-step]')];
    const previous = document.getElementById('activityWizardPrevious');
    const next = document.getElementById('activityWizardNext');
    const submit = document.getElementById('activityWizardSubmit');
    let current = 0;
    const input = (name) => form.querySelector(`[name="${name}"]`);
    const checkedValues = (name) => [...form.querySelectorAll(`[name="${name}"]:checked`)].map((el) => el.value);

    function setStep(step, shouldScroll = true) {
        current = Math.max(0, Math.min(panels.length - 1, step));
        panels.forEach((panel, index) => { panel.hidden = index !== current; });
        progress.forEach((button, index) => {
            button.classList.toggle('is-active', index === current);
            button.classList.toggle('is-complete', index < current);
            if (index === current) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
        previous.hidden = current === 0;
        next.hidden = current === panels.length - 1;
        submit.hidden = current !== panels.length - 1;
        if (current === panels.length - 1) renderSummary();
        panels[current].querySelector('h2')?.focus({ preventScroll: true });
        if (shouldScroll) panels[current].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function clearErrors(panel) {
        panel.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        panel.querySelectorAll('.wizard-field-error, .wizard-group-error').forEach((node) => { node.textContent = ''; });
    }

    function validatePanel(index) {
        const panel = panels[index];
        clearErrors(panel);
        let valid = true;
        panel.querySelectorAll('[data-required]').forEach((field) => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                const error = field.parentElement.querySelector('.wizard-field-error');
                if (error) error.textContent = `${field.dataset.label || 'Medan ini'} diperlukan.`;
                valid = false;
            }
        });
        const groups = new Set([...panel.querySelectorAll('[data-required-group]')].map((el) => el.dataset.requiredGroup));
        groups.forEach((group) => {
            if (!panel.querySelector(`[data-required-group="${group}"]:checked`)) {
                const error = panel.querySelector(`[data-group-error="${group}"]`);
                if (error) error.textContent = 'Pilih sekurang-kurangnya satu pilihan.';
                valid = false;
            }
        });

        if (index === 2) {
            const startDate = input('start_date').value;
            const endDate = input('end_date').value;
            const startTime = input('start_time').value;
            const endTime = input('end_time').value;
            if (startDate && endDate && endDate < startDate) {
                input('end_date').classList.add('is-invalid');
                input('end_date').parentElement.querySelector('.wizard-field-error').textContent = 'Tarikh tamat tidak boleh sebelum tarikh mula.';
                valid = false;
            } else if (startDate && endDate === startDate && startTime && endTime && endTime <= startTime) {
                input('end_time').classList.add('is-invalid');
                input('end_time').parentElement.querySelector('.wizard-field-error').textContent = 'Masa tamat mesti selepas masa mula.';
                valid = false;
            }
        }
        if (index === 3) {
            const incompleteBudget = [...form.querySelectorAll('.budget-row')].some((row) => {
                const values = [...row.querySelectorAll('[data-field]')].map((field) => field.value.trim());
                return values.some(Boolean) && values.some((value) => !value);
            });
            if (incompleteBudget) {
                valid = false;
                document.getElementById('budgetRows').scrollIntoView({ behavior: 'smooth', block: 'center' });
                form.querySelector('.budget-row input:invalid')?.focus();
                const emptyField = [...form.querySelectorAll('.budget-row')].flatMap((row) => [...row.querySelectorAll('[data-field]')]).find((field) => {
                    const rowValues = [...field.closest('.budget-row').querySelectorAll('[data-field]')].map((el) => el.value.trim());
                    return rowValues.some(Boolean) && !field.value.trim();
                });
                if (emptyField) {
                    emptyField.classList.add('is-invalid');
                    emptyField.parentElement.querySelector('.wizard-field-error').textContent = 'Lengkapkan medan ini atau buang baris bajet.';
                }
            }
        }
        return valid;
    }

    function updateRepeatNames(container) {
        const rows = [...document.getElementById(container).children];
        const config = {
            tentativeRows: { prefix: 'tentative', fields: ['time', 'description'] },
            committeeRows: { prefix: 'committee', fields: ['name', 'position'] },
            budgetRows: { prefix: 'budget_items', fields: ['description', 'quantity', 'estimated_cost'] },
        }[container];
        rows.forEach((row, index) => config.fields.forEach((field) => {
            const control = row.querySelector(`[data-field="${field}"]`);
            if (control) control.name = `${config.prefix}[${index}][${field}]`;
        }));
    }

    function updateBudgetTotal() {
        let total = 0;
        document.querySelectorAll('.budget-row').forEach((row) => {
            const quantity = Number(row.querySelector('[data-field="quantity"]').value || 0);
            const cost = Number(row.querySelector('[data-field="estimated_cost"]').value || 0);
            const rowTotal = quantity * cost;
            total += rowTotal;
            row.querySelector('.budget-row-total').textContent = `RM ${rowTotal.toFixed(2)}`;
        });
        document.getElementById('budgetGrandTotal').textContent = `RM ${total.toFixed(2)}`;
        return total;
    }

    function renderSummary() {
        const value = (name) => input(name)?.value?.trim() || 'Belum diisi';
        const list = (values) => values.length ? values.join(', ') : 'Belum dipilih';
        const rowText = (selector, fields) => [...form.querySelectorAll(selector)].map((row) => fields.map((field) => row.querySelector(`[data-field="${field}"]`)?.value?.trim()).filter(Boolean).join(' — ')).filter(Boolean);
        const sections = [
            ['Maklumat Program', [
                ['Nama', value('title')], ['Jenis', value('activity_type')], ['Kategori', value('program_category')],
                ['Jabatan / Unit', value('organizing_unit')], ['Pegawai Bertanggungjawab', value('person_in_charge')],
            ]],
            ['Objektif & Peserta', [
                ['Objektif', list([...form.querySelectorAll('[name="objectives[]"]')].map((el) => el.value.trim()).filter(Boolean))],
                ['Sasaran', list(checkedValues('target_participants[]'))], ['Anggaran Peserta', value('expected_participants')], ['Kriteria', value('participant_criteria')],
            ]],
            ['Tarikh, Tempat & Tentatif', [
                ['Tarikh / Masa', `${value('start_date')} ${value('start_time')} hingga ${value('end_date')} ${value('end_time')}`],
                ['Tempat', value('location')], ['Mod', value('implementation_mode')], ['Tentatif', list(rowText('.tentative-row', ['time', 'description']))],
            ]],
            ['Jawatankuasa & Kewangan', [
                ['Jawatankuasa', list(rowText('.committee-row', ['name', 'position']))], ['Item Bajet', list(rowText('.budget-row', ['description', 'quantity', 'estimated_cost']))],
                ['Jumlah Anggaran', `RM ${updateBudgetTotal().toFixed(2)}`], ['Sumber Dana', list(checkedValues('funding_sources[]'))],
            ]],
        ];
        document.getElementById('activityWizardSummary').innerHTML = sections.map(([title, rows]) => `<section class="activity-wizard-review-section"><h3>${title}</h3><dl>${rows.map(([label, text]) => `<div><dt>${label}</dt><dd>${escapeHtml(text)}</dd></div>`).join('')}</dl></section>`).join('');
        const missing = [];
        panels.slice(0, 4).forEach((panel) => panel.querySelectorAll('[data-required]').forEach((field) => {
            if (!field.value.trim()) missing.push(field.dataset.label || 'Medan wajib');
        }));
        if (!checkedValues('target_participants[]').length) missing.push('Sasaran Peserta');
        if (!checkedValues('funding_sources[]').length) missing.push('Sumber Dana');
        const missingBox = document.getElementById('activityWizardMissing');
        missingBox.hidden = missing.length === 0;
        missingBox.querySelector('ul').innerHTML = [...new Set(missing)].map((text) => `<li>${escapeHtml(text)}</li>`).join('');
    }

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
    }

    document.querySelectorAll('[data-go-step]').forEach((button) => button.addEventListener('click', () => {
        const destination = Number(button.dataset.goStep);
        if (destination <= current || (destination === current + 1 && validatePanel(current))) setStep(destination);
    }));
    next.addEventListener('click', () => { if (validatePanel(current)) setStep(current + 1); });
    previous.addEventListener('click', () => setStep(current - 1));

    document.querySelectorAll('[data-add-row]').forEach((button) => button.addEventListener('click', () => {
        const template = document.getElementById(button.dataset.template);
        const container = document.getElementById(button.dataset.addRow);
        const clone = template.content.cloneNode(true);
        container.append(clone);
        if (button.dataset.addRow !== 'objectivesRows') updateRepeatNames(button.dataset.addRow);
        updateBudgetTotal();
    }));

    form.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-row]');
        if (!button) return;
        const row = button.closest('.activity-wizard-repeat-row, tr');
        const container = row.parentElement;
        if (container.children.length > 1) row.remove();
        else row.querySelectorAll('input').forEach((field) => { field.value = ''; });
        if (container.id !== 'objectivesRows') updateRepeatNames(container.id);
        updateBudgetTotal();
    });
    form.addEventListener('input', (event) => {
        if (event.target.closest('#budgetRows')) updateBudgetTotal();
    });
    form.addEventListener('change', () => { if (current === panels.length - 1) renderSummary(); });
    form.addEventListener('submit', (event) => {
        const intent = event.submitter?.value;
        if (intent === 'draft') return;
        for (let index = 0; index < 4; index++) {
            if (!validatePanel(index)) {
                event.preventDefault();
                setStep(index);
                document.getElementById('activityWizardMissing').hidden = false;
                return;
            }
        }
    });

    ['tentativeRows', 'committeeRows', 'budgetRows'].forEach(updateRepeatNames);
    updateBudgetTotal();
    setStep(0, false);
})();
</script>
@endpush
