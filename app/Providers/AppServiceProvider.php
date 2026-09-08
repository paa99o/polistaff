<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use App\Models\PortalNotification;
use App\Models\EmailDelivery;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $header = $event->message->getHeaders()->get('X-Polistaff-Email-Delivery-ID');
            $deliveryId = $header?->getBodyAsString();

            if ($deliveryId && $delivery = EmailDelivery::find($deliveryId)) {
                $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                $delivery->notification?->update(['email_status' => 'sent', 'email_sent_at' => now(), 'email_error' => null]);
            }
        });
    }
}
