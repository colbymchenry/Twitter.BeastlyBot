<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;
use \App\StripeConnect;
use \App\TwitterAccount;
use \App\NewSubscription;

use \App\ScheduledInvoicePayout;
use \App\TwitterStore;
use \App\Subscription;
use \App\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentSucceeded implements ShouldQueue
{
    public function handle(WebhookCall $webhookCall)
    {
        \Log::info(json_encode($webhookCall->payload));
        $reason = $webhookCall->payload['data']['object']['billing_reason'];
        $plan_id = $webhookCall->payload['data']['object']['lines']['data'][0]['plan']['id'];
        $paid = $webhookCall->payload['data']['object']['paid'];

        if($paid && strpos($plan_id, '_') !== false) {
            $subscription_id = $webhookCall->payload['data']['object']['lines']['data'][0]['subscription'];
            $data = explode('_', $plan_id);
            
            if($data[0] == 'twitter') {
                $customer = $webhookCall->payload['data']['object']['customer'];
                $customer_id = StripeConnect::where('customer_id', $customer)->first()->user_id;
                $customer_twitter_id = TwitterAccount::where('user_id', $customer_id)->first()->twitter_id;
                $partner_id = $webhookCall->payload['data']['object']['lines']['data'][0]['plan']['metadata']['user_id'];
                $partner_twitter_id = TwitterAccount::where('user_id', $partner_id)->first()->twitter_id;

                $twitter_store = TwitterStore::where("twitter_id", $partner_twitter_id)->first();
                $twitter_helper = new \App\TwitterHelper(\App\User::where('id', $customer_id)->first());

                    // $stats = Stat::where('type', 1)->where('type_id', $twitter_store->id)->first();
                    // $subscribers_active = $stats->data['subscribers']['active'];
                    // $subscribers_total = $stats->data['subscribers']['total'];

                    // if($subscribers_active >= 1000){
                        $level = 1;
                    // }elseif($subscribers_active >= 100){
                    //     $level = 2;
                    // }else{
                    //     $level = 3;
                    // }

                if($reason == 'subscription_create') {
                    Cache::forget('customer_subscriptions_active_' . $customer_id);
                    Cache::forget('customer_subscriptions_canceled_' . $customer_id);

                    $customer_oauth = TwitterAccount::where('twitter_id', $customer_twitter_id)->first();
                    $partner_oauth = TwitterAccount::where('twitter_id', $partner_twitter_id)->first();
                    try {
                        $request_token = [
                            'token'  => $customer_oauth->oauth_token,
                            'secret' => $customer_oauth->oauth_token_secret,
                        ];
                
                        \Twitter::reconfig($request_token);
                        \Twitter::postFollow(['user_id' => $partner_twitter_id]);

                        $request_token = [
                            'token'  => $partner_oauth->oauth_token,
                            'secret' => $partner_oauth->oauth_token_secret,
                        ];
                
                        \Twitter::reconfig($request_token);
                        \Twitter::postFollow(['user_id' => $customer_twitter_id]);

                        $subscription = new Subscription();
                        $subscription->id = $subscription_id;
                        $subscription->stripe_connect_id = StripeConnect::where('user_id', $partner_id)->first()->id;
                        $subscription->latest_invoice_id = $webhookCall->payload['data']['object']['id'];
                        $subscription->latest_invoice_paid_at = date('Y-m-d H:i:s');
                        $subscription->latest_invoice_amount = $webhookCall->payload['data']['object']['amount_paid'];
                        $subscription->connection_type = 1;
                        $subscription->connection_id = TwitterAccount::where('user_id', $partner_id)->first()->id;
                        $subscription->store_id = $twitter_store->id;
                        $subscription->product_id = $webhookCall->payload['data']['object']['lines']['data'][0]['plan']['product'];
                        $subscription->refund_enabled = $twitter_store->refunds_enabled;
                        $subscription->refund_days = $twitter_store->refunds_days;
                        $subscription->refund_terms = $twitter_store->refunds_terms;
                        $subscription->status = 1;
                        $subscription->metadata = [];
                        $subscription->level = $level;
                        $subscription->user_id = $customer_id;
                        $subscription->partner_id = $partner_id;
                        $subscription->current_period_end = date("Y-m-d", $webhookCall->payload['data']['object']['lines']['data'][0]['period']['end']);
                        $subscription->save();

                        // $stats = Stat::where('type', 1)->where('type_id', $twitter_store->id)->first();                            
                        // $stats_data = $stats->data;
                        // $stats_data['subscribers'] = ['active' => $subscribers_active + 1, 'total' => $subscribers_total + 1];
                        // $stats->data = $stats_data;
                        // $stats->save();

                    } catch(\Exception $e) {
                        if (env('APP_DEBUG')) Log::error($e);
                    }
                
                } else if($reason == 'subscription_cycle') {
                    // try {
                    //     $guild = $discord_helper->getGuild($guild_id);
                    //     $role = $discord_helper->getRole($guild_id, $role_id);
                    //     $discord_helper->sendMessage('You recurring subscription to the ' . $role->name . ' role in the ' . $guild->name . ' server has just been renewed!');

                    //     $subscription = Subscription::where('id', $subscription_id)->first();
                    //     $subscription->latest_invoice_id = $webhookCall->payload['data']['object']['id']; 
                    //     $subscription->latest_invoice_paid_at = date('Y-m-d H:i:s');
                    //     $subscription->latest_invoice_amount = $webhookCall->payload['data']['object']['amount_paid'];
                    //     $subscription->status = $status;
                    //     $subscription->level = $discord_store->level;
                    //     $subscription->current_period_end = date("Y-m-d", $webhookCall->payload['data']['object']['lines']['data'][0]['period']['end']);
                    //     $subscription->save();
                    // } catch(\Exception $e) {
                    //     $discord_helper->sendMessage('Uh-oh! Your recurring subscription could not be renewed! I canceled the subscription and refunded your primary payment method.');
                    //     $discord_error = new \App\DiscordError();
                    //     $discord_error->guild_id = $guild_id;
                    //     $discord_error->role_id = $role_id;
                    //     $discord_error->user_id = $customer_id;
                    //     $discord_error->message = $e->getMessage();
                    //     $this->cancelRefund($webhookCall);
                    // }
                }
             
            }
        }
    }

    private function cancelRefund($webhookCall) {
        \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));

        try {
            \Stripe\Refund::create([
                'charge' => $webhookCall->payload['data']['object']['charge'],
            ]);
        } catch(\Exception $e) {
            $stripe_error = new \App\StripeError();
            $stripe_error->invoice_id = $webhookCall->payload['data']['object']['id'];
            $customer = $webhookCall->payload['data']['object']['customer'];
            $stripe_error->user_id = StripeConnect::where('customer_id', $customer)->first()->user_id;
            $stripe_error->message = $e->getMessage();
            $stripe_error->save();
        }

        $subscription_id = $webhookCall->payload['data']['object']['lines']['data'][0]['subscription'];
        $stripe = new \Stripe\StripeClient(env('STRIPE_CLIENT_SECRET'));

        try {
            $stripe->subscriptions->cancel($subscription_id, []);
        } catch(\Exception $e) {
            $stripe_error = new \App\StripeError();
            $stripe_error->invoice_id = $webhookCall->payload['data']['object']['id'];
            $customer = $webhookCall->payload['data']['object']['customer'];
            $stripe_error->user_id = StripeConnect::where('customer_id', $customer)->first()->user_id;
            $stripe_error->message = $e->getMessage();
            $stripe_error->save();
        }
    }

    // {
    //     "id": "evt_1HQGQCHTMWe6sDFb6rfPRTmk",
    //     "object": "event",
    //     "api_version": "2019-08-14",
    //     "created": 1599846747,
    //     "data": {
    //       "object": {
    //         "id": "in_1HQGQAHTMWe6sDFbJ86OHH5l",
    //         "object": "invoice",
    //         "account_country": "US",
    //         "account_name": "BeastlyBot",
    //         "amount_due": 1000,
    //         "amount_paid": 1000,
    //         "amount_remaining": 0,
    //         "application_fee_amount": null,
    //         "attempt_count": 1,
    //         "attempted": true,
    //         "auto_advance": false,
    //         "billing": "charge_automatically",
    //         "billing_reason": "subscription_create",
    //         "charge": "ch_1HQGQBHTMWe6sDFbDcWDMrkF",
    //         "collection_method": "charge_automatically",
    //         "created": 1599846746,
    //         "currency": "usd",
    //         "custom_fields": null,
    //         "customer": "cus_Hzejn102gol501",
    //         "customer_address": null,
    //         "customer_email": "colbymchenry@gmail.com",
    //         "customer_name": null,
    //         "customer_phone": null,
    //         "customer_shipping": null,
    //         "customer_tax_exempt": "none",
    //         "customer_tax_ids": [
    //         ],
    //         "default_payment_method": null,
    //         "default_source": null,
    //         "default_tax_rates": [
    //         ],
    //         "description": null,
    //         "discount": null,
    //         "discounts": [
    //         ],
    //         "due_date": null,
    //         "ending_balance": 0,
    //         "footer": null,
    //         "hosted_invoice_url": "https://pay.stripe.com/invoice/acct_1FF2w1HTMWe6sDFb/invst_I0GyBoM3EENpBgXOYOCON26f96eH7G5",
    //         "invoice_pdf": "https://pay.stripe.com/invoice/acct_1FF2w1HTMWe6sDFb/invst_I0GyBoM3EENpBgXOYOCON26f96eH7G5/pdf",
    //         "lines": {
    //           "object": "list",
    //           "data": [
    //             {
    //               "id": "sli_bc37a7b46c8156",
    //               "object": "line_item",
    //               "amount": 1000,
    //               "currency": "usd",
    //               "description": "1 × Premium Callout Group (at $10.00 / month)",
    //               "discount_amounts": [
    //               ],
    //               "discountable": true,
    //               "discounts": [
    //               ],
    //               "livemode": false,
    //               "metadata": {
    //               },
    //               "period": {
    //                 "end": 1602438746,
    //                 "start": 1599846746
    //               },
    //               "plan": {
    //                 "id": "discord_590728038849708043_596078383906029590_1_r",
    //                 "object": "plan",
    //                 "active": true,
    //                 "aggregate_usage": null,
    //                 "amount": 1000,
    //                 "amount_decimal": "1000",
    //                 "billing_scheme": "per_unit",
    //                 "created": 1599704617,
    //                 "currency": "usd",
    //                 "interval": "month",
    //                 "interval_count": 1,
    //                 "livemode": false,
    //                 "metadata": {
    //                   "user_id": "1"
    //                 },
    //                 "nickname": null,
    //                 "product": "discord_590728038849708043_596078383906029590",
    //                 "tiers": null,
    //                 "tiers_mode": null,
    //                 "transform_usage": null,
    //                 "trial_period_days": null,
    //                 "usage_type": "licensed"
    //               },
    //               "price": {
    //                 "id": "discord_590728038849708043_596078383906029590_1_r",
    //                 "object": "price",
    //                 "active": true,
    //                 "billing_scheme": "per_unit",
    //                 "created": 1599704617,
    //                 "currency": "usd",
    //                 "livemode": false,
    //                 "lookup_key": null,
    //                 "metadata": {
    //                   "user_id": "1"
    //                 },
    //                 "nickname": null,
    //                 "product": "discord_590728038849708043_596078383906029590",
    //                 "recurring": {
    //                   "aggregate_usage": null,
    //                   "interval": "month",
    //                   "interval_count": 1,
    //                   "trial_period_days": null,
    //                   "usage_type": "licensed"
    //                 },
    //                 "tiers_mode": null,
    //                 "transform_quantity": null,
    //                 "type": "recurring",
    //                 "unit_amount": 1000,
    //                 "unit_amount_decimal": "1000"
    //               },
    //               "proration": false,
    //               "quantity": 1,
    //               "subscription": "sub_I0GyJhJrdhdlV8",
    //               "subscription_item": "si_I0GyVmbmxD6YwT",
    //               "tax_amounts": [
    //               ],
    //               "tax_rates": [
    //               ],
    //               "type": "subscription",
    //               "unique_id": "il_1HQGQAHTMWe6sDFbsP905rL2"
    //             }
    //           ],
    //           "has_more": false,
    //           "total_count": 1,
    //           "url": "/v1/invoices/in_1HQGQAHTMWe6sDFbJ86OHH5l/lines"
    //         },
    //         "livemode": false,
    //         "metadata": {
    //         },
    //         "next_payment_attempt": null,
    //         "number": "399ED757-0027",
    //         "paid": true,
    //         "payment_intent": "pi_1HQGQAHTMWe6sDFb4FsqTJaN",
    //         "period_end": 1599846746,
    //         "period_start": 1599846746,
    //         "post_payment_credit_notes_amount": 0,
    //         "pre_payment_credit_notes_amount": 0,
    //         "receipt_number": null,
    //         "starting_balance": 0,
    //         "statement_descriptor": null,
    //         "status": "paid",
    //         "status_transitions": {
    //           "finalized_at": 1599846746,
    //           "marked_uncollectible_at": null,
    //           "paid_at": 1599846747,
    //           "voided_at": null
    //         },
    //         "subscription": "sub_I0GyJhJrdhdlV8",
    //         "subtotal": 1000,
    //         "tax": null,
    //         "tax_percent": null,
    //         "total": 1000,
    //         "total_discount_amounts": [
    //         ],
    //         "total_tax_amounts": [
    //         ],
    //         "transfer_data": null,
    //         "webhooks_delivered_at": null
    //       }
    //     },
    //     "livemode": false,
    //     "pending_webhooks": 3,
    //     "request": {
    //       "id": "req_82cS4VMHkr2BYR",
    //       "idempotency_key": null
    //     },
    //     "type": "invoice.payment_succeeded"
    //   }
}