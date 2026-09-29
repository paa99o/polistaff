<?php

namespace App\Console\Commands;

use App\Models\PolimartItem;
use App\Models\PolimartOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePolimartOrders extends Command
{
    protected $signature = 'polimart:expire-orders';

    protected $description = 'Cancel unpaid PoliMart orders and release their reserved stock.';

    public function handle(): int
    {
        PolimartOrder::query()
            ->where('status', 'pending')
            ->whereIn('payment_status', ['awaiting_payment', 'rejected'])
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($orders): void {
                foreach ($orders as $candidate) {
                    DB::transaction(function () use ($candidate): void {
                        $order = PolimartOrder::query()->lockForUpdate()->find($candidate->id);
                        if (! $order || $order->status !== 'pending' || ! in_array($order->payment_status, ['awaiting_payment', 'rejected'], true) || $order->payment_expires_at?->isFuture()) {
                            return;
                        }

                        $orderItems = collect($order->items);
                        $items = PolimartItem::whereIn('id', $orderItems->pluck('id'))->lockForUpdate()->get()->keyBy('id');
                        foreach ($orderItems as $orderItem) {
                            $item = $items->get((int) ($orderItem['id'] ?? 0));
                            if (! $item) {
                                continue;
                            }
                            $item->stock += (int) ($orderItem['quantity'] ?? 0);
                            if ($item->status === 'sold' && $item->stock > 0) {
                                $item->status = 'active';
                            }
                            $item->save();
                        }

                        $order->update(['status' => 'cancelled', 'payment_status' => 'expired']);
                    });
                }
            });

        return self::SUCCESS;
    }
}
