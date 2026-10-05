<?php

namespace App\Services;

use App\Models\PolimartItem;
use App\Models\PolimartOrder;
use Illuminate\Support\Facades\DB;

class PolimartOrderExpiryService
{
    public function expireDueOrders(): int
    {
        $expiredCount = 0;

        PolimartOrder::query()
            ->where('status', 'pending')
            ->whereIn('payment_status', ['awaiting_payment', 'rejected'])
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$expiredCount): void {
                foreach ($orders as $candidate) {
                    $expired = DB::transaction(function () use ($candidate): bool {
                        $order = PolimartOrder::query()->lockForUpdate()->find($candidate->id);
                        if (! $order
                            || $order->status !== 'pending'
                            || ! in_array($order->payment_status, ['awaiting_payment', 'rejected'], true)
                            || $order->payment_expires_at?->isFuture()) {
                            return false;
                        }

                        $orderItems = collect($order->items);
                        $items = PolimartItem::whereIn('id', $orderItems->pluck('id'))
                            ->lockForUpdate()
                            ->get()
                            ->keyBy('id');

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

                        return true;
                    });

                    if ($expired) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }
}
