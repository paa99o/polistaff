<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PolimartItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PolimartController extends Controller
{
    public function index(): View
    {
        return view('polimart.index', [
            'items' => PolimartItem::with('user')->where('status', 'active')->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('polimart.create');
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

    public function destroy(PolimartItem $polimartItem): RedirectResponse
    {
        abort_unless($polimartItem->user_id === auth()->id() || auth()->user()->hasRole('admin'), 403);

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
}
