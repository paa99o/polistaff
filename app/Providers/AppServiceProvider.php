<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use App\Models\PortalNotification;
use App\Models\EmailDelivery;
use App\Models\User;
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

        Event::listen(NotificationSending::class, function (NotificationSending $event): void {
            if ($event->channel !== 'mail' || ! $event->notifiable instanceof User || ! $event->notifiable->email) {
                return;
            }

            EmailDelivery::create([
                'user_id' => $event->notifiable->id,
                'event' => str(class_basename($event->notification))->snake(),
                'recipient' => $event->notifiable->email,
                'mailable' => $event->notification::class,
                'status' => 'queued',
                'attempts' => 1,
            ]);
        });

        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            if ($event->channel !== 'mail' || ! $event->notifiable instanceof User) {
                return;
            }

            $delivery = EmailDelivery::where('user_id', $event->notifiable->id)
                ->where('mailable', $event->notification::class)
                ->where('status', 'queued')
                ->latest()
                ->first();

            $delivery?->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
        });

        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            if ($event->channel !== 'mail' || ! $event->notifiable instanceof User) {
                return;
            }

            $delivery = EmailDelivery::where('user_id', $event->notifiable->id)
                ->where('mailable', $event->notification::class)
                ->where('status', 'queued')
                ->latest()
                ->first();

            $delivery?->update(['status' => 'failed', 'error' => $event->exception->getMessage()]);
        });
    }
}
