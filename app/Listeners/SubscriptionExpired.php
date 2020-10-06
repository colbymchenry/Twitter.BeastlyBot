<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;

use App\User;
use App\Subscription;
use App\StripeConnect;
use App\StripeHelper;
use App\DiscordStore;
use App\PaidOutInvoice;
use \App\Stat;

class SubscriptionExpired implements ShouldQueue
{
    // TODO: TEST
    public function handle(WebhookCall $webhookCall)
    {
        SubscriptionCanceled::execute($webhookCall);
    }

}