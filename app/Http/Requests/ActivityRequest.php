<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class ActivityRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('start_date') && $this->filled('start_time')) {
            $this->merge(['date_time' => $this->input('start_date').' '.$this->input('start_time')]);
        }

        if ($this->filled('activity_date') && $this->filled('start_time')) {
            $this->merge(['date_time' => $this->input('activity_date').' '.$this->input('start_time')]);
        }
    }

    public function rules(): array
    {
        if ($this->input('wizard') === '1') {
            $draft = $this->input('intent') === 'draft';
            $required = $draft ? 'nullable' : 'required';
            $dateOrderRule = $draft ? [] : ['after_or_equal:start_date'];
            $registrationCloseRules = $draft
                ? ['nullable', 'date']
                : ['nullable', 'date', 'after_or_equal:registration_opens_at', 'before_or_equal:date_time'];

            return [
                'wizard' => ['required', Rule::in(['1'])],
                'intent' => ['required', Rule::in(['draft', 'submit'])],
                'title' => [$required, 'string', 'max:255'],
                'activity_type' => [$required, Rule::in(['Seminar', 'Workshop', 'Training', 'Competition', 'Meeting', 'Visit', 'Community Program', 'Others'])],
                'program_category' => [$required, 'string', 'max:150'],
                'organizing_unit' => [$required, 'string', 'max:150'],
                'person_in_charge' => [$required, 'string', 'max:150'],
                'description' => [$required, 'string', 'max:10000'],
                'objectives' => [$required, 'array', 'min:1'],
                'objectives.*' => [$draft ? 'nullable' : 'required', 'string', 'max:1000'],
                'target_participants' => [$required, 'array', 'min:1'],
                'target_participants.*' => [$draft ? 'nullable' : 'required', Rule::in(['Student', 'Staff', 'Lecturer', 'External Community', 'Others'])],
                'expected_participants' => [$required, 'integer', 'min:1', 'max:100000'],
                'participant_criteria' => ['nullable', 'string', 'max:3000'],
                'start_date' => [$required, 'date'],
                'end_date' => [$required, 'date', ...$dateOrderRule],
                'start_time' => [$required, 'date_format:H:i'],
                'end_time' => [$required, 'date_format:H:i'],
                'location' => [$required, 'string', 'max:255'],
                'implementation_mode' => [$required, Rule::in(['Physical', 'Online', 'Hybrid'])],
                'tentative' => [$required, 'array', 'min:1'],
                'tentative.*.time' => [$draft ? 'nullable' : 'required', 'date_format:H:i'],
                'tentative.*.description' => [$draft ? 'nullable' : 'required', 'string', 'max:1000'],
                'committee' => [$required, 'array', 'min:1'],
                'committee.*.name' => [$draft ? 'nullable' : 'required', 'string', 'max:150'],
                'committee.*.position' => [$draft ? 'nullable' : 'required', 'string', 'max:150'],
                'budget_items' => ['nullable', 'array'],
                'budget_items.*.description' => ['nullable', 'string', 'max:255'],
                'budget_items.*.quantity' => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
                'budget_items.*.estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
                'funding_sources' => [$required, 'array', 'min:1'],
                'funding_sources.*' => [Rule::in(['Department Allocation', 'Participant Fee', 'Sponsorship', 'Grant', 'Others'])],
                'registration_opens_at' => ['nullable', 'date'],
                'registration_closes_at' => $registrationCloseRules,
            ];
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'date_time' => ['required', 'date'],
            'activity_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'location' => ['required', 'string', 'max:255'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at', 'before_or_equal:date_time'],
            'status' => ['required', 'in:draft,pending_approval,approved,cancelled'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('wizard') !== '1' || $this->input('intent') === 'draft') {
                return;
            }

            if (
                $this->filled('start_date') && $this->filled('end_date') && $this->filled('start_time') && $this->filled('end_time')
                && ! $validator->errors()->hasAny(['start_date', 'end_date', 'start_time', 'end_time'])
            ) {
                $startsAt = Carbon::parse($this->input('start_date').' '.$this->input('start_time'));
                $endsAt = Carbon::parse($this->input('end_date').' '.$this->input('end_time'));
                if ($endsAt->lessThanOrEqualTo($startsAt)) {
                    $validator->errors()->add('end_time', 'Tarikh dan masa tamat mesti selepas tarikh dan masa mula.');
                }
            }

            foreach ((array) $this->input('budget_items', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (filled($item['description'] ?? null) || filled($item['quantity'] ?? null) || filled($item['estimated_cost'] ?? null)) {
                    foreach (['description', 'quantity', 'estimated_cost'] as $field) {
                        if (blank($item[$field] ?? null)) {
                            $validator->errors()->add("budget_items.$index.$field", 'Lengkapkan semua medan item bajet atau buang baris ini.');
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'registration_closes_at.after_or_equal' => 'Registration Closes mesti sama atau selepas Registration Opens.',
            'registration_closes_at.before_or_equal' => 'Registration Closes mesti pada atau sebelum tarikh aktiviti.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tajuk',
            'date_time' => 'tarikh dan masa',
            'activity_date' => 'tarikh aktiviti',
            'start_time' => 'masa bermula',
            'end_time' => 'masa berakhir',
            'location' => 'lokasi',
            'max_participants' => 'maksimum peserta',
            'registration_opens_at' => 'registration opens',
            'registration_closes_at' => 'registration closes',
            'status' => 'status',
        ];
    }
}
