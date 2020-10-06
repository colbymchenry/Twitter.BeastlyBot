<?php

namespace App\Products;

use App\TwitterStore;
use App\TwitterAccount;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RestCord\DiscordClient;
use App\TwitterHelper;

class TwitterProduct extends Product
{

    public $twitter_id, $billing_cycle;
    public $twitter_store;
    public $twitter_account;

    public function __construct($twitter_id, $billing_cycle)
    {
        $this->twitter_id = $twitter_id;
        $this->billing_cycle = $billing_cycle;
        
        if(TwitterStore::where('twitter_id', $twitter_id)->exists()) {
            $this->twitter_store = TwitterStore::where('twitter_id', $twitter_id)->first();
        }

        if(TwitterAccount::where('twitter_id', $twitter_id)->exists()) {
            $this->twitter_account = TwitterAccount::where('twitter_id', $twitter_id)->first();
        }

        parent::__construct('twitter');
    }
  
    public function checkoutValidate(): void {
        if($this->twitter_store == null)
            throw new ProductMsgException('Twitter store not found in database.');

        if(!$this->twitter_store->live)
            throw new ProductMsgException('Sorry, purchases are disabled in testing mode.');

        $twitter_helper = new \App\TwitterHelper(auth()->user());

        if (auth()->user()->getStripeHelper()->isSubscribedToProduct($this->getStripeID())) {
            throw new ProductMsgException('You are already subscribed. You can edit your subscription in the dashboard.');
        }
        
    }

    public function create(Request $request) {
        $this->createProduct();

        if($this->twitter_store == null) {
            $this->twitter_store = TwitterStore::create([
                'twitter_id' => $this->twitter_id,
                'url' => $this->twitter_id,
                'user_id' => $this->twitter_account->user_id
            ]);
        }

        return response()->json(['success' => true, 'msg' => 'Product created!', 'active' => true]);
    }

    public function update(Request $request) {
        $this->createProduct();

        try {
            if($this->twitter_store == null) {
                $this->twitter_store = TwitterStore::create([
                    'twitter_id' => $this->twitter_id,
                    'url' => $this->twitter_id,
                    'user_id' => $this->twitter_account->user_id
                ]);
            }

            if($this->getStripeProduct() !== null) {
                if($this->getStripeProduct()['active']) {
                    \Stripe\Product::update($this->getStripeID(), ['active' => false]);
                    Cache::forget('product_' . $this->getStripeID());
                    return response()->json(['success' => true, 'active' => false]);
                } else {
                    \Stripe\Product::update($this->getStripeID(), ['active' => true]);
                    Cache::forget('product_' . $this->getStripeID());
                    return response()->json(['success' => true, 'active' => true]);
                }
            }
        } catch(\Exception $e) {
            \Log::error($e);
            return response()->json(['success' => false, 'msg' => 'Something went wrong on Stripe\'s end. Try again later.']);
        }

    }

    public function getCallbackParams(): array
    {
        return ['twitter_id' => $this->twitter_account->twitter_id];
    }

    public function getStripeID(): string
    {
        return 'twitter_' . $this->twitter_id;
    }

    public function checkoutSuccess()
    {
        return redirect('/dashboard');
    }

    public function checkoutCancel()
    {
        return redirect('/shop/' . $this->twitter_store->url);
    }

    public function getStripePlan() {
        \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
        return \Stripe\Plan::retrieve($this->getStripeID() . '_' . $this->billing_cycle . '_r');
    }


    public function createProduct() {
        if($this->getStripeProduct() == null) {
            \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
            try {
                $this->stripe_product_obj = \Stripe\Product::retrieve($this->getStripeID());
                Cache::put('product_' . $this->getStripeID(), $this->stripe_product_obj, 60 * 10);
            } catch (\Exception $e) {
            }

            if($this->stripe_product_obj == null) {
                $this->stripe_product_obj = \Stripe\Product::create([
                    'name' => $this->twitter_account->screen_name,
                    'id' => $this->getStripeID(),
                    'type' => 'service',
                    'metadata' => ['user_id' => auth()->user()->id],
                ]);
                Cache::put('product_' . $this->getStripeID(), $this->stripe_product_obj, 60 * 10);
            }
        }
    }

}
