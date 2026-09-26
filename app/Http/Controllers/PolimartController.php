<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PolimartItem;
use App\Models\PolimartFavorite;
use App\Models\PolimartOrder;
use App\Models\PolimartReview;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
                $query->where('name', 'like', '%'.$search.'%');
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
            'favoriteIds' => $request->user()
                ? PolimartFavorite::where('user_id', $request->user()->id)->pluck('polimart_item_id')->all()
                : [],
        ]);
    }

    public function publicIndex(Request $request): View
    {
        if ($request->user()) {
            return $this->index($request);
        }

        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $itemsQuery = PolimartItem::with('user')
            ->whereIn('status', ['active', 'sold'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category));

        return view('public.polimart', [
            'items' => $itemsQuery->latest()->paginate(12)->withQueryString(),
            'categories' => PolimartItem::whereIn('status', ['active', 'sold'])->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'search' => $search,
            'category' => $category,
        ]);
    }

    public function publicShow(PolimartItem $polimartItem): View
    {
        if (auth()->check()) {
            return $this->show($polimartItem);
        }

        abort_unless(in_array($polimartItem->status, ['active', 'sold'], true), 404);

        return view('public.polimart-show', ['item' => $polimartItem->load('user')]);
    }

    public function cart(Request $request): View
    {
        return view('public.polimart-cart', $this->cartData($request));
    }

    public function addToCart(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        abort_unless($polimartItem->status === 'active' && $polimartItem->stock > 0, 422, 'Produk ini sudah habis stok.');
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:99']]);
        $cart = $request->session()->get('polimart_cart', []);
        $quantity = (int) ($data['quantity'] ?? 1);
        $requestedQuantity = (int) ($cart[$polimartItem->id] ?? 0) + $quantity;
        if ($requestedQuantity > $polimartItem->stock) {
            return back()->with('status', 'Kuantiti melebihi stok yang tinggal. Stok semasa: '.$polimartItem->stock.'.');
        }
        $cart[$polimartItem->id] = min(99, $requestedQuantity);
        $request->session()->put('polimart_cart', $cart);

        return back()->with('status', $polimartItem->name.' ditambah ke troli.');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $quantities = $request->validate(['quantities' => ['array']])['quantities'] ?? [];
        $removeId = (int) $request->input('remove', 0);
        $availableItems = PolimartItem::whereIn('id', array_keys($quantities))
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->get()
            ->keyBy('id');
        $cart = [];
        foreach ($quantities as $itemId => $quantity) {
            $item = $availableItems->get((int) $itemId);
            if ($item && (int) $quantity > 0) {
                $cart[(int) $itemId] = min(99, (int) $quantity, (int) $item->stock);
            }
        }
        if ($removeId > 0) {
            unset($cart[$removeId]);
        }
        $request->session()->put('polimart_cart', $cart);

        return back()->with('status', 'Troli berjaya dikemas kini.');
    }

    public function removeFromCart(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        $cart = $request->session()->get('polimart_cart', []);
        unset($cart[$polimartItem->id]);
        $request->session()->put('polimart_cart', $cart);

        return back()->with('status', 'Produk dibuang daripada troli.');
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $cart = $this->cartData($request);
        if ($cart['items']->isEmpty()) {
            return redirect()->route('polimart.index')->with('status', 'Troli anda masih kosong.');
        }

        return view('public.polimart-checkout', $cart);
    }

    public function placeOrder(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:180'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:20'],
            'state' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ]);
        $cart = $this->cartData($request);
        abort_if($cart['items']->isEmpty(), 422, 'Troli anda masih kosong.');

        $order = DB::transaction(function () use ($request, $data, $cart): PolimartOrder {
            $itemIds = $cart['items']->pluck('item.id')->all();
            $lockedItems = PolimartItem::with('user')
                ->whereIn('id', $itemIds)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $orderItems = [];
            $subtotal = 0;

            foreach ($cart['items'] as $line) {
                $item = $lockedItems->get($line['item']->id);
                if (! $item || $item->stock < $line['quantity']) {
                    throw ValidationException::withMessages(['cart' => 'Stok '.$line['item']->name.' baru sahaja berubah. Sila semak troli anda semula.']);
                }

                $orderItems[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $line['quantity'],
                    'price' => (float) $item->price,
                    'seller' => $item->user->name,
                ];
                $subtotal += (float) $item->price * $line['quantity'];
                $item->stock -= $line['quantity'];
                $item->status = $item->stock === 0 ? 'sold' : 'active';
                $item->save();
            }

            $shippingFee = $subtotal > 0 ? 7 : 0;

            return PolimartOrder::create([
                ...$data,
                'order_number' => 'PM-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'items' => $orderItems,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $subtotal + $shippingFee,
                'status' => 'pending',
            ]);
        });
        $request->session()->forget('polimart_cart');

        return view('public.polimart-order-success', compact('order'));
    }

    public function orders(): View
    {
        return view('admin.polimart-orders', ['orders' => PolimartOrder::latest()->paginate(20)]);
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
        abort_unless(in_array($polimartItem->status, ['active', 'sold'], true), 404);
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
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
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
            'status' => (int) $data['stock'] > 0 ? 'active' : 'sold',
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
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
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
        if (in_array($polimartItem->status, ['active', 'sold'], true)) {
            $polimartItem->update(['status' => (int) $polimartItem->stock > 0 ? 'active' : 'sold']);
        }

        return redirect()->route('polimart.show', $polimartItem)->with('status', 'Listing PoliMart dikemas kini.');
    }

    public function updateStatus(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        $this->authorizeListing($polimartItem);
        $data = $request->validate(['status' => ['required', 'in:active,reserved,sold']]);
        abort_if($data['status'] === 'active' && $polimartItem->stock < 1, 422, 'Masukkan stok sekurang-kurangnya 1 sebelum mengaktifkan listing.');
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

    private function cartData(Request $request): array
    {
        $cart = collect($request->session()->get('polimart_cart', []));
        $items = PolimartItem::with('user')->whereIn('id', $cart->keys())->where('status', 'active')->where('stock', '>', 0)->get()->keyBy('id');
        $lines = $cart->map(function ($quantity, $itemId) use ($items): ?array {
            $item = $items->get((int) $itemId);
            if (! $item) {
                return null;
            }
            $safeQuantity = min((int) $item->stock, 99, max(1, (int) $quantity));
            return ['item' => $item, 'quantity' => $safeQuantity, 'lineTotal' => (float) $item->price * $safeQuantity];
        })->filter()->values();
        $subtotal = $lines->sum('lineTotal');
        $shippingFee = $lines->isEmpty() ? 0 : 7;

        return ['items' => $lines, 'subtotal' => $subtotal, 'shippingFee' => $shippingFee, 'total' => $subtotal + $shippingFee, 'cartCount' => $lines->sum('quantity')];
    }
}
