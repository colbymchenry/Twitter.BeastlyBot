<?php

namespace App\Products\Plans;

use Illuminate\Support\Facades\Cache;
use \App\TwitterStore;

class TwitterPlan extends Plan
{

    public function update(\Illuminate\Http\Request $request)
    {
        $this->product->createProduct();

        $stripe = new \Stripe\StripeClient(env('STRIPE_CLIENT_SECRET'));

        if(! \App\TwitterStore::where('twitter_id', $this->product->twitter_id)->exists()) {
            $this->product->twitter_store = TwitterStore::create([
                'twitter_id' => $this->product->twitter_id,
                'url' => $this->product->twitter_id,
                'user_id' => $this->product->twitter_account->user_id
            ]);
        }
        
        $same_price = false;

        if($this->getStripePlan() !== null) {
            if($this->getStripePlan()->amount != intval($request['price']) * 100) {
                try {
                    $stripe->plans->delete($this->getStripeID(), []);
                } catch (\Exception $e) {}
            } else {
                $same_price = true;
            }
        }

        if($request['price'] > 0 && !$same_price) {
            $plan = $stripe->plans->create([
                "amount" => intval($request['price']) * 100,
                "interval" => $this->interval,
                "interval_count" => $this->interval_cycle,
                "product" => $this->product->getStripeID(),
                "currency" => "usd",
                'metadata' => [
                    'user_id' => auth()->user()->id
                ],
                "id" => $this->getStripeID(),
            ]);

            Cache::forget('plan_' . $this->getStripeID());
            Cache::put('plan_' . $this->getStripeID(), $plan, 60 * 10);
        }
        
        return response()->json(['success' => true, 'msg' => 'Prices updated.']);
    }

    public function create(\Illuminate\Http\Request $request)
    {
        $this->product->createProduct();
        
        try {
            parent::create($request);
            if(! \App\TwitterStore::where('twitter_id', $this->product->twitter_id)->exists()) {
                $this->product->twitter_store = TwitterStore::create([
                    'twitter_id' => $this->product->twitter_id,
                    'url' => $this->product->twitter_id,
                    'user_id' => $this->product->twitter_account->user_id
                ]);
            }
            $key = 'price_' . $this->product->getStripeID() . '_' . $this->interval_cycle;
            Cache::put($key, $request['price'], 60 * 5);
        } catch(\Exception $e) {
            \Log::info($e);
        }

        return response()->json(['success' => true, 'msg' => 'Plan created.']);
    }

    public function getStripeID(): string
    {
        return $this->product->getStripeID() . '_' . $this->interval_cycle . '_r';
    }

}