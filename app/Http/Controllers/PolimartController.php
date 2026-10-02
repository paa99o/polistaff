<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PolimartItem;
use App\Models\PolimartFavorite;
use App\Models\PolimartOrder;
use App\Models\PolimartSellerPaymentProfile;
use App\Models\PolimartReview;
use App\Models\User;
use App\Mail\PolimartOrderStatusMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PolimartController extends Controller
{
    private const FPX_BANKS = ['Maybank', 'CIMB', 'Bank Islam', 'RHB', 'Public Bank', 'Hong Leong Bank', 'AmBank', 'BSN', 'OCBC', 'Alliance Bank'];
    private const BANK_HOME_PAGES = [
        'Maybank' => 'https://www.maybank2u.com.my/maybank2u/malaysia/en/personal/index.page',
        'CIMB' => 'https://www.cimb.com.my/en/personal/home.html',
        'Bank Islam' => 'https://www.bankislam.com/',
        'RHB' => 'https://www.rhbgroup.com/index.html',
        'Public Bank' => 'https://www.publicbankgroup.com/',
        'Hong Leong Bank' => 'https://www.hlb.com.my/en/personal-banking/home.html',
        'AmBank' => 'https://www.ambank.com.my/',
        'BSN' => 'https://www.bsn.com.my/',
        'OCBC' => 'https://www.ocbc.com.my/personal-banking/home',
        'Alliance Bank' => 'https://www.alliancebank.com.my/',
    ];

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
        $existingSellerIds = PolimartItem::whereIn('id', array_keys($cart))->where('status', 'active')->pluck('user_id')->unique();
        if ($existingSellerIds->isNotEmpty() && ! $existingSellerIds->contains($polimartItem->user_id)) {
            return back()->with('status', 'Setiap pesanan PoliMart hanya boleh mengandungi produk daripada seorang penjual. Selesaikan atau kosongkan troli dahulu.');
        }
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
        $sellerIds = $availableItems->only(array_keys($cart))->pluck('user_id')->unique();
        if ($sellerIds->count() > 1) {
            return back()->with('status', 'Troli hanya boleh mengandungi produk daripada seorang penjual. Troli asal dikekalkan.');
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

        $sellerId = $cart['items']->first()['item']->user_id;
        $paymentProfile = PolimartSellerPaymentProfile::where('user_id', $sellerId)->first();

        return view('public.polimart-checkout', [...$cart, 'paymentProfile' => $paymentProfile, 'fpxBanks' => self::FPX_BANKS]);
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
            'payment_method' => ['required', 'in:qr,fpx'],
            'fpx_bank' => ['required_if:payment_method,fpx', 'nullable', 'string', Rule::in(self::FPX_BANKS)],
            'terms' => ['accepted'],
        ]);
        $cart = $this->cartData($request);
        abort_if($cart['items']->isEmpty(), 422, 'Troli anda masih kosong.');
        abort_if($cart['items']->pluck('item.user_id')->unique()->count() > 1, 422, 'Setiap pesanan PoliMart hanya boleh mengandungi produk daripada seorang penjual.');
        $sellerId = $cart['items']->first()['item']->user_id;
        $paymentProfile = PolimartSellerPaymentProfile::where('user_id', $sellerId)->first();
        if (! $this->sellerHasPaymentMethod($paymentProfile, $data['payment_method'])) {
            throw ValidationException::withMessages(['payment_method' => 'Penjual belum menyediakan maklumat bagi kaedah bayaran yang dipilih.']);
        }

        $order = DB::transaction(function () use ($request, $data, $cart, $paymentProfile): PolimartOrder {
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
                    'seller_id' => $item->user_id,
                    'name' => $item->name,
                    'quantity' => $line['quantity'],
                    'price' => (float) $item->price,
                    'seller' => $item->user->name,
                    'seller_contact' => $item->contact,
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
                'payment_method' => $data['payment_method'],
                'payment_instructions' => $this->paymentInstructions($paymentProfile, $data['payment_method'], $data['fpx_bank'] ?? null),
                'payment_status' => 'awaiting_payment',
                'payment_expires_at' => now()->addHours(24),
            ]);
        });
        $request->session()->forget('polimart_cart');
        $trackingUrl = $this->orderTrackingUrl($order);
        $trackingEmailSent = $this->sendOrderEmail($order, $trackingUrl);
        $sellerContacts = $this->sellerContactLinks($order);

        if ($order->payment_method === 'fpx') {
            $bank = $order->payment_instructions['fpx_bank'] ?? null;
            if (isset(self::BANK_HOME_PAGES[$bank])) {
                return redirect()->away(self::BANK_HOME_PAGES[$bank]);
            }
        }

        return view('public.polimart-order-success', compact('order', 'trackingUrl', 'trackingEmailSent', 'sellerContacts'));
    }

    public function fpxPayment(PolimartOrder $polimartOrder): RedirectResponse
    {
        abort_unless($polimartOrder->payment_method === 'fpx', 404);
        $bank = $polimartOrder->payment_instructions['fpx_bank'] ?? '';
        abort_unless(isset(self::BANK_HOME_PAGES[$bank]), 404);

        return redirect()->away(self::BANK_HOME_PAGES[$bank]);
    }

    public function trackOrder(PolimartOrder $polimartOrder): View
    {
        return view('public.polimart-order-tracking', [
            'order' => $polimartOrder,
            'sellerContacts' => $this->sellerContactLinks($polimartOrder),
            'proofSubmitUrl' => URL::temporarySignedRoute('polimart.orders.payment-proof.submit', now()->addDays(90), ['polimartOrder' => $polimartOrder->id]),
        ]);
    }

    public function submitOrderPaymentProof(Request $request, PolimartOrder $polimartOrder): RedirectResponse
    {
        abort_unless(in_array($polimartOrder->payment_status, ['awaiting_payment', 'rejected'], true), 422, 'Bukti bayaran tidak boleh dihantar untuk status ini.');
        abort_unless($polimartOrder->status === 'pending' && (! $polimartOrder->payment_expires_at || $polimartOrder->payment_expires_at->isFuture()), 422, 'Tempoh pembayaran pesanan ini telah tamat.');
        $data = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        if ($polimartOrder->payment_proof_path) {
            Storage::disk('private')->delete($polimartOrder->payment_proof_path);
        }
        $path = $request->file('payment_proof')->store('polimart-payment-proofs', 'private');
        $polimartOrder->update(['payment_proof_path' => $path, 'payment_reference' => $data['payment_reference'] ?? null, 'payment_review_note' => null, 'payment_status' => 'proof_submitted', 'payment_expires_at' => now()->addHours(24)]);

        return back()->with('status', 'Bukti bayaran diterima. Penjual perlu menyemak transaksi sebenar sebelum mengesahkan bayaran.');
    }

    public function confirmOrderPayment(Request $request, PolimartOrder $polimartOrder): RedirectResponse
    {
        abort_unless($this->userCanManageOrder($request->user(), $polimartOrder), 403);
        abort_unless($polimartOrder->payment_status === 'proof_submitted', 422, 'Pesanan ini belum mempunyai bukti bayaran untuk disemak.');
        $polimartOrder->update(['payment_status' => 'paid', 'payment_paid_at' => now(), 'payment_review_note' => null]);
        $polimartOrder->refresh();
        $this->sendOrderEmail($polimartOrder, $this->orderTrackingUrl($polimartOrder));

        return back()->with('status', 'Bayaran ditandakan diterima selepas semakan transaksi.');
    }

    public function rejectOrderPaymentProof(Request $request, PolimartOrder $polimartOrder): RedirectResponse
    {
        abort_unless($this->userCanManageOrder($request->user(), $polimartOrder), 403);
        abort_unless($polimartOrder->payment_status === 'proof_submitted', 422, 'Tiada bukti menunggu semakan.');
        $data = $request->validate(['payment_review_note' => ['required', 'string', 'max:1000']]);
        $polimartOrder->update(['payment_status' => 'rejected', 'payment_review_note' => $data['payment_review_note']]);
        $polimartOrder->refresh();
        $this->sendOrderEmail($polimartOrder, $this->orderTrackingUrl($polimartOrder));

        return back()->with('status', 'Bukti ditolak. Pembeli boleh hantar bukti baharu melalui pautan semakan pesanan.');
    }

    public function confirmOrderRefund(Request $request, PolimartOrder $polimartOrder): RedirectResponse
    {
        abort_unless($this->userCanManageOrder($request->user(), $polimartOrder), 403);
        abort_unless($polimartOrder->payment_status === 'refund_required', 422, 'Pesanan ini tidak menunggu pemulangan bayaran.');
        $polimartOrder->update(['payment_status' => 'refunded']);
        $polimartOrder->refresh();
        $this->sendOrderEmail($polimartOrder, $this->orderTrackingUrl($polimartOrder));

        return back()->with('status', 'Pemulangan wang ditandakan selesai. Pastikan pindahan balik telah dibuat di luar PoliMart.');
    }

    public function orderPaymentProof(Request $request, PolimartOrder $polimartOrder)
    {
        abort_unless($this->userCanManageOrder($request->user(), $polimartOrder), 403);
        abort_unless($polimartOrder->payment_proof_path, 404);

        return Storage::disk('private')->response($polimartOrder->payment_proof_path);
    }

    public function paymentSettings(Request $request): View
    {
        return view('polimart.payment-settings', [
            'paymentProfile' => $request->user()->polimartSellerPaymentProfile,
        ]);
    }

    public function updatePaymentSettings(Request $request): RedirectResponse
    {
        $profile = $request->user()->polimartSellerPaymentProfile;
        $data = $request->validate([
            'qr_code' => ['nullable', 'image', 'max:4096'],
            'bank_name' => ['nullable', 'string', 'max:120', 'required_with:account_name,account_number'],
            'account_name' => ['nullable', 'string', 'max:120', 'required_with:bank_name,account_number'],
            'account_number' => ['nullable', 'string', 'max:80', 'required_with:bank_name,account_name'],
        ]);

        abort_if(
            ! $request->hasFile('qr_code')
                && ! $profile?->qr_code_path
                && blank($data['bank_name'] ?? null),
            422,
            'Sediakan QR atau butiran akaun bank sebelum menyimpan.',
        );

        if ($request->hasFile('qr_code')) {
            if ($profile?->qr_code_path) {
                Storage::disk('public')->delete($profile->qr_code_path);
            }
            $data['qr_code_path'] = $request->file('qr_code')->store('polimart-payment-qr', 'public');
        }
        unset($data['qr_code']);

        PolimartSellerPaymentProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data,
        );

        return back()->with('status', 'Maklumat bayaran PoliMart berjaya disimpan.');
    }

    public function orders(Request $request): View
    {
        $orders = PolimartOrder::latest();
        if (! $request->user()->hasRole('admin')) {
            $sellerItemIds = PolimartItem::where('user_id', $request->user()->id)->pluck('id')->all();
            $orders->where(function ($query) use ($sellerItemIds, $request): void {
                $query->whereJsonContains('items', ['seller_id' => $request->user()->id]);
                foreach ($sellerItemIds as $id) {
                    $query->orWhereJsonContains('items', ['id' => $id]);
                }
            });
        }

        return view('admin.polimart-orders', ['orders' => $orders->paginate(20)]);
    }

    public function updateOrderStatus(Request $request, PolimartOrder $polimartOrder): RedirectResponse
    {
        abort_unless($this->userCanManageOrder($request->user(), $polimartOrder), 403);
        $data = $request->validate(['status' => ['required', 'in:confirmed,completed,cancelled']]);

        DB::transaction(function () use ($polimartOrder, $data): void {
            $order = PolimartOrder::query()->lockForUpdate()->findOrFail($polimartOrder->id);
            $nextStatus = $data['status'];
            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
            ];

            abort_unless(in_array($nextStatus, $allowedTransitions[$order->status] ?? [], true), 422, 'Perubahan status pesanan ini tidak dibenarkan.');
            if ($nextStatus === 'confirmed') {
                abort_unless($order->payment_status === 'paid', 422, 'Sahkan bayaran terlebih dahulu sebelum memproses pesanan.');
            }
            if ($nextStatus === 'cancelled') {
                abort_unless($order->payment_status !== 'proof_submitted', 422, 'Semak bukti dan bayaran terlebih dahulu sebelum membatalkan pesanan.');
            }

            if ($nextStatus === 'cancelled') {
                $orderItems = collect($order->items);
                $items = PolimartItem::whereIn('id', $orderItems->pluck('id'))->lockForUpdate()->get()->keyBy('id');

                foreach ($orderItems as $orderItem) {
                    $item = $items->get((int) ($orderItem['id'] ?? 0));
                    if (! $item) {
                        continue;
                    }

                    $item->stock += (int) $orderItem['quantity'];
                    if ($item->status === 'sold') {
                        $item->status = 'active';
                    }
                    $item->save();
                }
            }

            $order->update([
                'status' => $nextStatus,
                ...($nextStatus === 'cancelled' ? [
                    'payment_status' => $order->payment_status === 'paid' ? 'refund_required' : 'cancelled',
                ] : []),
            ]);
        });

        $polimartOrder->refresh();
        $this->sendOrderEmail($polimartOrder, $this->orderTrackingUrl($polimartOrder));

        return back()->with('status', 'Status pesanan PoliMart dikemas kini.');
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
            'canReview' => $this->buyerCanReview(auth()->user(), $polimartItem),
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
        abort_unless($this->buyerCanReview($request->user(), $polimartItem), 403);
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
        abort_if(
            PolimartOrder::whereIn('status', ['pending', 'confirmed'])
                ->whereJsonContains('items', ['id' => $polimartItem->id])
                ->exists(),
            422,
            'Listing tidak boleh dipadam selagi ada pesanan yang belum selesai.',
        );

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

    private function buyerCanReview(User $user, PolimartItem $item): bool
    {
        return PolimartOrder::query()
            ->where('customer_email', $user->email)
            ->where('status', 'completed')
            ->get()
            ->contains(fn (PolimartOrder $order): bool => collect($order->items)->contains(
                fn (array $orderItem): bool => (int) ($orderItem['id'] ?? 0) === $item->id,
            ));
    }

    private function orderTrackingUrl(PolimartOrder $order): string
    {
        return URL::temporarySignedRoute(
            'polimart.orders.track',
            now()->addDays(90),
            ['polimartOrder' => $order->id],
        );
    }

    private function userCanManageOrder(?\App\Models\User $user, PolimartOrder $order): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        if (! $user->hasRole('member')) {
            return false;
        }
        $items = collect($order->items);
        if ($items->contains(fn (array $item): bool => (int) ($item['seller_id'] ?? 0) === $user->id)) {
            return true;
        }
        $itemIds = $items->pluck('id')->map(fn ($id) => (int) $id);
        return PolimartItem::where('user_id', $user->id)->whereIn('id', $itemIds)->exists();
    }

    private function sellerContactLinks(PolimartOrder $order): array
    {
        return collect($order->items)
            ->pluck('seller_contact')
            ->filter()
            ->unique()
            ->map(function (string $contact) use ($order): array {
                $number = preg_replace('/\D+/', '', $contact);
                if (str_starts_with($number, '0')) {
                    $number = '60'.substr($number, 1);
                }

                return [
                    'contact' => $contact,
                    'url' => strlen($number) >= 9
                        ? 'https://wa.me/'.$number.'?text='.rawurlencode('Salam, saya ingin membuat bayaran QR/transfer untuk pesanan '.$order->order_number.'. Boleh kongsikan butiran bayaran?')
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    private function sellerHasPaymentMethod(?PolimartSellerPaymentProfile $profile, string $method): bool
    {
        if (! $profile) {
            return false;
        }

        return $method === 'qr'
            ? filled($profile->qr_code_path)
            : filled($profile->bank_name) && filled($profile->account_name) && filled($profile->account_number);
    }

    private function paymentInstructions(PolimartSellerPaymentProfile $profile, string $method, ?string $fpxBank = null): array
    {
        if ($method === 'qr') {
            return [
                'method' => 'qr',
                'qr_code_path' => $profile->qr_code_path,
                'account_name' => $profile->account_name,
            ];
        }

        return [
            'method' => 'fpx',
            'fpx_bank' => $fpxBank,
            'bank_name' => $profile->bank_name,
            'account_name' => $profile->account_name,
            'account_number' => $profile->account_number,
        ];
    }

    private function sendOrderEmail(PolimartOrder $order, string $trackingUrl): bool
    {
        try {
            Mail::to($order->customer_email)->send(new PolimartOrderStatusMail($order, $trackingUrl));
            return true;
        } catch (Throwable $exception) {
            Log::warning('Unable to send PoliMart order email.', [
                'order_id' => $order->id,
                'exception' => $exception->getMessage(),
            ]);
            return false;
        }
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
