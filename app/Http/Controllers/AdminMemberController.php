<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminMemberController extends Controller
{
    public function pending(): View
    {
        return view('admin.members.pending', ['members' => User::where('membership_status', 'pending')->latest()->paginate(15)]);
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update(['membership_status' => 'active', 'joined_date' => now()->toDateString()]);

        return back()->with('status', 'Ahli berjaya diluluskan.');
    }
}
