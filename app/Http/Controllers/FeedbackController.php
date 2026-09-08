<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedbackRequest;
use App\Models\Activity;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasRole('admin', 'chairman'), 403);

        $feedbacks = Feedback::with('user', 'activity')
            ->when($request->filled('rating'), fn ($query) => $query->where('rating', $request->rating))
            ->when($request->filled('activity'), fn ($query) => $query->whereHas('activity', fn ($activityQuery) => $activityQuery->where('title', 'like', '%'.$request->activity.'%')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.feedback.index', ['feedbacks' => $feedbacks]);
    }

    public function create(): View
    {
        return view('feedback.create', ['activities' => Activity::where('status', 'approved')->orderByDesc('date_time')->get()]);
    }

    public function store(FeedbackRequest $request): RedirectResponse
    {
        Feedback::create([...$request->validated(), 'user_id' => $request->user()->id]);

        return redirect()->route('dashboard')->with('status', 'Maklum balas berjaya dihantar.');
    }
}
