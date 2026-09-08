<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPreferenceRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        return view('preferences.edit', ['user' => $request->user(), 'title' => 'Tetapan Saya']);
    }

    public function update(UserPreferenceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $before = $user->only($this->preferenceFields());
        $data = $request->validated();

        foreach ($this->booleanFields() as $field) {
            $data[$field] = $request->boolean($field);
        }

        $user->update($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'module' => 'User Preferences',
            'record_type' => get_class($user),
            'record_id' => $user->id,
            'description' => 'Updated personal notification and display preferences.',
            'changes' => ['before' => $before, 'after' => $user->fresh()->only($this->preferenceFields())],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('preferences.edit')->with('status', 'Tetapan anda berjaya disimpan.');
    }

    private function preferenceFields(): array
    {
        return ['theme_preference', 'text_size_preference', ...$this->booleanFields()];
    }

    private function booleanFields(): array
    {
        return ['reduce_motion', 'email_announcements', 'email_activities', 'email_finance', 'email_fee_reminders'];
    }
}
