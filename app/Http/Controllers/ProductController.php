<?php

namespace App\Http\Controllers;

use App\DiscordStore;
use App\ProductRole;
use App\Ban;
use Illuminate\Support\Facades\Cache;

use App\Products\TwitterProduct;
use App\Products\Plans\TwitterPlan;
use App\Products\ProductMsgException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function product(Request $request) {
        $interval_cycle = $request['interval_cycle'];

        try {
            // find the product type to initiate
            switch ($request['product_type']) {
                case "twitter":
                    $product = new TwitterProduct(auth()->user()->getTwitterAccount()->twitter_id, $interval_cycle);
                break;
                default:
                    throw new ProductMsgException('Could not find product by that type.');
                break;
            }

            \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
            if($request['action'] == 'delete') {
                return $product->delete($request);
            } else {
                if($product->getStripeProduct() == null) {
                    return $product->create($request);
                } else {
                    return $product->update($request);
                }
            }

        } catch(\Exception $e) {
            \Log::info($e);
        } catch(ProductMsgException $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        } catch(\Stripe\Exception\ApiErrorException $e) {
            if(env('APP_DEBUG')) Log::error($e);
            return response()->json(['success' => false, 'msg' => $e->getError()->message]);
        }
    }

    public function plan(Request $request) {
        $interval = $request['interval'];
        $interval_cycle = $request['interval_cycle'];

        try {
            // find the product type to initiate
            switch ($request['product_type']) {
                case "twitter":
                    $plan = new TwitterPlan(new TwitterProduct(auth()->user()->getTwitterAccount()->twitter_id, $interval_cycle), $interval, $interval_cycle);
                break;
                default:
                    throw new ProductMsgException('Could not find product by that type.');
                break;
            }

            \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
            if($request['action'] == 'delete') {
                return $plan->delete($request);
            } else {
                if($plan->getProduct()->getStripeProduct() == null) {
                    $plan->getProduct()->create($request);
                }
                if($plan->getStripePlan() == null) {
                    return $plan->create($request);
                } else {
                    return $plan->update($request);
                }
            }

        } catch(ProductMsgException $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        } catch(\Stripe\Exception\ApiErrorException $e) {
            if(env('APP_DEBUG')) Log::error($e);
            return response()->json(['success' => false, 'msg' => $e->getError()->message]);
        }
    }

    public static function getPricesTwitterAccount($twitter_id) {
        $prices = [];
        // Any time accessing Stripe API this snippet of code must be ran above any preceding API calls
        \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
        foreach ([1, 3, 6, 12] as $duration) {
            $twitter_plan = new TwitterPlan(new TwitterProduct($twitter_id, $duration), 'month', $duration);
            $key = 'plan_' . $twitter_plan->getStripeID();
            
            if($twitter_plan->getStripePlan() != null) {
                $prices[$duration] = $twitter_plan->getStripePlan()->amount / 100;
            }
        }
        return $prices;
    }


    public function getShop($url) {
        if(! \App\TwitterStore::where('url', $url)->exists()) {
            \Log::info('Could not find Twitter Store URL: ' . $url);
            return abort(404);
        }

        $twitter_store = \App\TwitterStore::where('url', $url)->first();
        $owner_array = \App\User::where('id', $twitter_store->user_id)->first();
        $twitter_helper = new \App\TwitterHelper(auth()->user());

       /* if(Ban::where('user_id', auth()->user()->id)->where('active', 1)->where('type', 1)->where('discord_store_id', $discord_store->id)->exists() && auth()->user()->id != $discord_store->user_id){
            return abort(404);
        }*/
 
        if(!$owner_array->getStripeHelper()->hasActiveExpressPlan()){
            // $twitter_store->live = false;
            // $twitter_store->save();
        }

        if(!$twitter_store->live && auth()->user()->id != $twitter_store->user_id){
            return view('offline');
        }

        $subscribers = [];

        $twitter_account = \App\TwitterAccount::where('twitter_id', $twitter_store->twitter_id)->first();

        return view('subscribe')->with('twitter_account', $twitter_account)->with('twitter_store', $twitter_store)->with('owner_array', \App\User::where('id', $twitter_account->user_id)->first());
    }

}
