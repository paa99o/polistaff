<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PolimartItem;
use App\Models\PolimartFavorite;
use App\Models\PolimartReview;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PolimartController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $mine = $request->boolean('mine');
        $favorites = $request->boolean('favorites');

        $itemsQuery = PolimartItem::with('user')
            ->when(! $mine, fn ($query) => $query->where('status', 'active'))
            ->when($mine, fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($favorites, fn ($query) => $query->whereHas('favorites', fn ($query) => $query->where('user_id', $request->user()->id)))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%');
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category));

        return view('polimart.index', [
            'items' => $itemsQuery->latest()->paginate(12)->withQueryString(),
            'categories' => PolimartItem::where('status', 'active')
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'search' => $search,
            'category' => $category,
            'mine' => $mine,
            'favorites' => $favorites,
            'favoriteIds' => PolimartFavorite::where('user_id', $request->user()->id)->pluck('polimart_item_id')->all(),
        ]);
    }

    public function favorites(Request $request): View
    {
        $request->query->set('favorites', '1');

        return $this->index($request);
    }

    public function create(): View
    {
        return view('polimart.create');
    }

    public function show(PolimartItem $polimartItem): View
    {
        abort_unless(
            in_array($polimartItem->status, ['active', 'reserved', 'sold'], true)
                || $polimartItem->user_id === auth()->id()
                || auth()->user()->hasRole('admin'),
            404,
        );

        return view('polimart.show', [
            'item' => $polimartItem->load(['user', 'reviews.user']),
            'isFavorited' => PolimartFavorite::where('user_id', auth()->id())->where('polimart_item_id', $polimartItem->id)->exists(),
        ]);
    }

    public function toggleFavorite(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        abort_unless($polimartItem->status === 'active', 404);
        $favorite = PolimartFavorite::where('user_id', $request->user()->id)->where('polimart_item_id', $polimartItem->id)->first();

        if ($favorite) {
            $favorite->delete();
            return back()->with('status', 'Listing dibuang daripada favorite.');
        }

        PolimartFavorite::create(['user_id' => $request->user()->id, 'polimart_item_id' => $polimartItem->id]);
        return back()->with('status', 'Listing disimpan dalam favorite.');
    }

    public function review(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        abort_unless($polimartItem->status === 'sold' && $polimartItem->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
        PolimartReview::updateOrCreate(
            ['user_id' => $request->user()->id, 'polimart_item_id' => $polimartItem->id],
            $data,
        );

        return back()->with('status', 'Review berjaya disimpan.');
    }

    public function seller(User $user): View
    {
        return view('polimart.seller', [
            'seller' => $user,
            'items' => $user->polimartItems()->where('status', 'active')->latest()->paginate(12),
        ]);
    }

    public function edit(PolimartItem $polimartItem): View
    {
        $this->authorizeListing($polimartItem);

        return view('polimart.edit', ['item' => $polimartItem]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:1200'],
            'contact' => ['required', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
        $imagePath = $request->file('image')?->store('polimart-items', 'public');
        unset($data['image']);

        $item = PolimartItem::create([
            ...$data,
            'user_id' => $request->user()->id,
            'image_path' => $imagePath,
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'module' => 'PoliMart',
            'record_type' => PolimartItem::class,
            'record_id' => $item->id,
            'description' => 'Created PoliMart listing '.$item->name.'.',
            'changes' => $item->only(['name', 'category', 'price', 'contact', 'image_path']),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('polimart.index')->with('status', 'Produk PoliMart berjaya diterbitkan.');
    }

    public function update(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        $this->authorizeListing($polimartItem);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:1200'],
            'contact' => ['required', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            if ($polimartItem->image_path) {
                Storage::disk('public')->delete($polimartItem->image_path);
            }
            $data['image_path'] = $request->file('image')->store('polimart-items', 'public');
        }
        unset($data['image']);
        $polimartItem->update($data);

        return redirect()->route('polimart.show', $polimartItem)->with('status', 'Listing PoliMart dikemas kini.');
    }

    public function updateStatus(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        $this->authorizeListing($polimartItem);
        $data = $request->validate(['status' => ['required', 'in:active,reserved,sold']]);
        $polimartItem->update($data);

        return back()->with('status', 'Status listing dikemas kini.');
    }

    public function destroy(PolimartItem $polimartItem): RedirectResponse
    {
        $this->authorizeListing($polimartItem);

        if ($polimartItem->image_path) {
            Storage::disk('public')->delete($polimartItem->image_path);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'module' => 'PoliMart',
            'record_type' => PolimartItem::class,
            'record_id' => $polimartItem->id,
            'description' => 'Deleted PoliMart listing '.$polimartItem->name.'.',
            'changes' => $polimartItem->only(['name', 'category', 'price', 'contact', 'image_path']),
            'ip_address' => request()->ip(),
        ]);

        $polimartItem->delete();

        return back()->with('status', 'Produk PoliMart dipadam.');
    }

    private function authorizeListing(PolimartItem $polimartItem): void
    {
        abort_unless($polimartItem->user_id === auth()->id() || auth()->user()->hasRole('admin'), 403);
    }
}
