<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('polimart_orders')
            ->where('status', 'pending')
            ->where('payment_status', 'awaiting_payment')
            ->whereNull('payment_expires_at')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('polimart_orders')->where('id', $order->id)->update([
                        'payment_expires_at' => Carbon::parse($order->created_at)->addHours(24),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Expiration timestamps are operational data and are intentionally retained.
    }
};
