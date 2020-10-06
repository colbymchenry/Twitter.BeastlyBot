<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;
use \App\EndedSubscription;
use \App\StripeConnect;
use \App\TwitterAccount;
use \App\TwitterStore;
use \App\Stat;
use Illuminate\Support\Facades\Cache;
use \App\Subscription;
use Illuminate\Support\Facades\Log;

class SubscriptionCanceled implements ShouldQueue
{
    public function handle(WebhookCall $webhookCall)
    {
        SubscriptionCanceled::execute($webhookCall);
    }

    public static function execute(WebhookCall $webhookCall) {
        $subscription_id = $webhookCall->payload['data']['object']['items']['data'][0]['subscription'];
        $plan_id = $webhookCall->payload['data']['object']['items']['data'][0]['plan']['id'];

      /*  try {
            $subscription = Subscription::where($subscription_id)->first();
            if($subscription->status <= 3){
                $subscription->status = 2;
                $subscription->save();
            }
        }
        catch (ApiErrorException $e) {
            if (env('APP_DEBUG')) Log::error($e);
            Log::info("Sub canceled (2) did not save in DB: ", $subscription_id);
            // Failed to Transfer
        }*/

        if(strpos($plan_id, 'twitter') !== false) {
            $customer = $webhookCall->payload['data']['object']['customer'];
            $customer_id = StripeConnect::where('customer_id', $customer)->first()->user_id;
            $customer_twitter_id = TwitterAccount::where('user_id', $customer_id)->first()->twitter_id;
            $partner_id = $webhookCall->payload['data']['object']['items']['data'][0]['plan']['metadata']['user_id'];
            $partner_twitter_id = TwitterAccount::where('user_id', $partner_id)->first()->twitter_id;

            $twitter_store = TwitterStore::where("twitter_id", $partner_twitter_id)->first();
            $twitter_helper = new \App\TwitterHelper(\App\User::where('id', $customer_id)->first());
            $data = explode('_', $plan_id);
        
            if($data[0] == 'twitter') {
                Cache::forget('customer_subscriptions_active_' . $customer_id);
                Cache::forget('customer_subscriptions_canceled_' . $customer_id);

                $customer_oauth = TwitterAccount::where('twitter_id', $customer_twitter_id)->first();
                $partner_oauth = TwitterAccount::where('twitter_id', $partner_twitter_id)->first();

                try {
                    $subscription = Subscription::where('id', $subscription_id)->first();
                    if($subscription->status <= 3) {
                        $subscription->status = 4;
                        $subscription->save();

                        $request_token = [
                            'token'  => $partner_oauth->oauth_token,
                            'secret' => $partner_oauth->oauth_token_secret,
                        ];
                        
                        try {
                            \Twitter::reconfig($request_token);
                            \Twitter::postBlock(['user_id' => $customer_twitter_id]);
                            \Twitter::destroyBlock(['user_id' => $customer_twitter_id]);
                        } catch(\Exception $e) {
                            \Log::error($e->getMessage());
                        }

                        \App\PendingFollowRequest::where('partner_twitter_id', $partner_twitter_id)->where('customer_twitter_id', $customer_twitter_id)->delete();
                    
                        // $stats = Stat::where('type', 1)->where('type_id', $discord_store->id)->first();
                        // $subscribers_active = $stats->data['subscribers']['active'];
                        // $subscribers_total = $stats->data['subscribers']['total'];

                        // $stats_data = $stats->data;
                        // $stats_data['subscribers'] = ['active' => $subscribers_active - 1, 'total' => $subscribers_total];
                        // $stats->data = $stats_data;
                        // $stats->save();
                    }
                }
                catch (ApiErrorException $e) {
                    if (env('APP_DEBUG')) Log::error($e);
                    Log::info("Sub canceled (5) did not save in DB: ", $subscription_id);
                    // Failed to Transfer
                }
            }
        }
    }

    #
    # IMMEDIATE CANCEL RESULT
    #
    #
    // {
    //     "id": "evt_1HZP3rHTMWe6sDFbiJaFt2Mr",
    //     "object": "event",
    //     "api_version": "2019-08-14",
    //     "created": 1602024911,
    //     "data": {
    //       "object": {
    //         "id": "sub_I9fbCVUrPFwSmA",
    //         "object": "subscription",
    //         "application_fee_percent": null,
    //         "billing": "charge_automatically",
    //         "billing_cycle_anchor": 1602014154,
    //         "billing_thresholds": null,
    //         "cancel_at": null,
    //         "cancel_at_period_end": false,
    //         "canceled_at": 1602024911,
    //         "collection_method": "charge_automatically",
    //         "created": 1602014154,
    //         "current_period_end": 1604692554,
    //         "current_period_start": 1602014154,
    //         "customer": "cus_I9fbK8qXGbpPoR",
    //         "days_until_due": null,
    //         "default_payment_method": "pm_1HZMGMHTMWe6sDFbHDYVQ5lD",
    //         "default_source": null,
    //         "default_tax_rates": [],
    //         "discount": null,
    //         "ended_at": 1602024911,
    //         "invoice_customer_balance_settings": {
    //           "consume_applied_balance_on_void": true
    //         },
    //         "items": {
    //           "object": "list",
    //           "data": [
    //             {
    //               "id": "si_I9fbFDmXpSYGKR",
    //               "object": "subscription_item",
    //               "billing_thresholds": null,
    //               "created": 1602014155,
    //               "metadata": [],
    //               "plan": {
    //                 "id": "twitter_2755941776_1_r",
    //                 "object": "plan",
    //                 "active": true,
    //                 "aggregate_usage": null,
    //                 "amount": 1000,
    //                 "amount_decimal": "1000",
    //                 "billing_scheme": "per_unit",
    //                 "created": 1602007829,
    //                 "currency": "usd",
    //                 "interval": "month",
    //                 "interval_count": 1,
    //                 "livemode": false,
    //                 "metadata": {
    //                   "user_id": "1"
    //                 },
    //                 "nickname": null,
    //                 "product": "twitter_2755941776",
    //                 "tiers": null,
    //                 "tiers_mode": null,
    //                 "transform_usage": null,
    //                 "trial_period_days": null,
    //                 "usage_type": "licensed"
    //               },
    //               "price": {
    //                 "id": "twitter_2755941776_1_r",
    //                 "object": "price",
    //                 "active": true,
    //                 "billing_scheme": "per_unit",
    //                 "created": 1602007829,
    //                 "currency": "usd",
    //                 "livemode": false,
    //                 "lookup_key": null,
    //                 "metadata": {
    //                   "user_id": "1"
    //                 },
    //                 "nickname": null,
    //                 "product": "twitter_2755941776",
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
    //               "quantity": 1,
    //               "subscription": "sub_I9fbCVUrPFwSmA",
    //               "tax_rates": []
    //             }
    //           ],
    //           "has_more": false,
    //           "total_count": 1,
    //           "url": "/v1/subscription_items?subscription=sub_I9fbCVUrPFwSmA"
    //         },
    //         "latest_invoice": "in_1HZMGMHTMWe6sDFbKbuFsaml",
    //         "livemode": false,
    //         "metadata": [],
    //         "next_pending_invoice_item_invoice": null,
    //         "pause_collection": null,
    //         "pending_invoice_item_interval": null,
    //         "pending_setup_intent": null,
    //         "pending_update": null,
    //         "plan": {
    //           "id": "twitter_2755941776_1_r",
    //           "object": "plan",
    //           "active": true,
    //           "aggregate_usage": null,
    //           "amount": 1000,
    //           "amount_decimal": "1000",
    //           "billing_scheme": "per_unit",
    //           "created": 1602007829,
    //           "currency": "usd",
    //           "interval": "month",
    //           "interval_count": 1,
    //           "livemode": false,
    //           "metadata": {
    //             "user_id": "1"
    //           },
    //           "nickname": null,
    //           "product": "twitter_2755941776",
    //           "tiers": null,
    //           "tiers_mode": null,
    //           "transform_usage": null,
    //           "trial_period_days": null,
    //           "usage_type": "licensed"
    //         },
    //         "quantity": 1,
    //         "schedule": null,
    //         "start": 1602014154,
    //         "start_date": 1602014154,
    //         "status": "canceled",
    //         "tax_percent": null,
    //         "transfer_data": null,
    //         "trial_end": null,
    //         "trial_start": null
    //       }
    //     },
    //     "livemode": false,
    //     "pending_webhooks": 2,
    //     "request": {
    //       "id": "req_suFodUvomW0FP9",
    //       "idempotency_key": null
    //     },
    //     "type": "customer.subscription.deleted"
    //   }
}