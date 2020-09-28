<?php

namespace App\Http\Controllers;

use App\DiscordStore;
use App\ProductRole;
use App\Ban;
use Illuminate\Support\Facades\Cache;

use App\Products\DiscordRoleProduct;
use App\Products\Plans\DiscordPlan;
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
                case "discord":
                    $product = new DiscordRoleProduct($request['guild_id'], $request['role_id'], $interval_cycle);
                break;
                default:
                    throw new ProductMsgException('Could not find product by that type.');
                break;
            }

            // TODO: Handle create and update as default case
            // find the action to execute
            \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
            switch($request['action']) {
                case "create":
                    return $product->create($request);
                case "delete":
                    return $product->delete($request);
                case "update":
                    return $product->update($request);
                default:
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
                case "discord":
                    $plan = new DiscordPlan(new DiscordRoleProduct($request['guild_id'], $request['role_id'], $interval_cycle), $interval, $interval_cycle);
                break;
                default:
                    throw new ProductMsgException('Could not find product by that type.');
                break;
            }
            // find the action to execute
            \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
            switch($request['action']) {
                case "create":
                    return $plan->create($request);
                case "delete":
                    return $plan->delete($request);
                case "update":
                    return $plan->update($request);
                default:
                {
                    if($plan->getStripePlan() == null) {
                        return $plan->create($request);
                    } else {
                        return $plan->update($request);
                    }
                }
            }
        } catch(ProductMsgException $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        } catch(\Stripe\Exception\ApiErrorException $e) {
            if(env('APP_DEBUG')) Log::error($e);
            return response()->json(['success' => false, 'msg' => $e->getError()->message]);
        }
    }

    // TODO: Fix this, this won't work properly
    public static function getPricesForRole($guild_id, $role_id) {
        $prices = [];
        // Any time accessing Stripe API this snippet of code must be ran above any preceding API calls
        \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
        foreach ([1, 3, 6, 12] as $duration) {
            $discord_plan = new DiscordPlan(new DiscordRoleProduct($guild_id, $role_id, $duration), 'month', $duration);
            $key = 'plan_' . $discord_plan->getStripeID();
            
            if($discord_plan->getStripePlan() != null) {
                $prices[$duration] = $discord_plan->getStripePlan()->amount / 100;
            } else {
                $prices[$duration] = 0;
            }
        }
        return $prices;
    }

    public function setProductDescription(Request $request) {
        $guild_id = $request['guild_id'];
        $role_id = $request['role_id'];
        $description = $request['description'];

        if(! auth()->user()->getDiscordHelper()->ownsGuild($guild_id)) 
            return response()->json(['success' => false, 'msg' => 'You are not the owner of this server.']);
        
        $store = DiscordStore::where('guild_id', $guild_id)->first();

        if(! ProductRole::where('discord_store_id', $store->id)->exists()) {
            $product_role = new ProductRole(['discord_store_id' => $store->id, 'role_id' => $role_id]);
        } else {
            $product_role = ProductRole::where('discord_store_id', $store->id)->first();
        }

        $product_role->description = $description;
        $product_role->save();
        return response()->json(['success' => true]);
    }

    public function getShop($url) {
        if(! DiscordStore::where('url', $url)->exists()) {
            return abort(404);
        }

        $discord_store = \App\DiscordStore::where('url', $url)->first();
        $owner_array = \App\User::where('id', $discord_store->first()->user_id)->first();
        $discord_helper = new \App\DiscordHelper(auth()->user());

       /* if(Ban::where('user_id', auth()->user()->id)->where('active', 1)->where('type', 1)->where('discord_store_id', $discord_store->id)->exists() && auth()->user()->id != $discord_store->user_id){
            return abort(404);
        }*/
 
        if(!$owner_array->getStripeHelper()->hasActiveExpressPlan()){
            $discord_store->live = false;
            $discord_store->save();
        }
        $discord_store = \App\DiscordStore::where('url', $url)->first();

        if(!$discord_store->live && auth()->user()->id != $discord_store->user_id){
            return view('offline');
        }

        $roles = $discord_helper->getRoles($discord_store->guild_id);
 
        $active = array();
        $subscribers = [];
        $descriptions = ProductRole::where('discord_store_id', $discord_store->id)->get();
 
        foreach($roles as $role) {
            $subscribers[$role->id] = Cache::get('subscribers_' . $role->id);
            $discord_product = new DiscordRoleProduct($discord_store->guild_id, $role->id, null);
            $stripe_product = $discord_product->getStripeProduct();
            if($stripe_product != null && $stripe_product->active) {
                array_push($active, $role->id);
                $subscribers[$role->id] = \App\Subscription::where('store_id', $discord_store->id)->where('status', '<=', 3)->where('metadata', 'LIKE', '%' . $role->id . '%')->count();
            } else {
                $subscribers[$role->id] = 0;
            }
        }


        $banned = $discord_helper->isUserBanned($discord_store->guild_id, \App\DiscordOAuth::where('user_id', auth()->user()->id)->first()->discord_id);

        return view('subscribe')->with('guild_id', $discord_store->guild_id)->with('descriptions', 'asd')->with('owner_array', $owner_array)->with('shop_url', $discord_store->url)->with('roles', $roles)->with('active', $active)->with('guild', $discord_helper->getGuild($discord_store->guild_id))->with('banned', $banned)->with('descriptions', $descriptions);
    }

}
