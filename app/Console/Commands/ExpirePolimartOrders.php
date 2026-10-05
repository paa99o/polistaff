<?php

namespace App\Console\Commands;

use App\Services\PolimartOrderExpiryService;
use Illuminate\Console\Command;

class ExpirePolimartOrders extends Command
{
    protected $signature = 'polimart:expire-orders';

    protected $description = 'Cancel unpaid PoliMart orders and release their reserved stock.';

    public function handle(PolimartOrderExpiryService $orderExpiryService): int
    {
        $expired = $orderExpiryService->expireDueOrders();
        $this->info("Expired {$expired} PoliMart order(s).");

        return self::SUCCESS;
    }
}
