<?php

use App\Shop;
use App\TwitterStore;
use App\TwitterHelper;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Products\TwitterProduct;
use App\Products\Plans\TwitterPlan;
use Illuminate\Support\Facades\Cache;

Route::get('/slide-product-purchase/{twitter_id}', function($twitter_id) {
    if(\request('affiliate_id') !== null) {
        return view('slide.slide-product-purchase')->with('shop', TwitterStore::where('twitter_id', $twitter_id)->first())->with('prices', ProductController::getPricesTwitterAccount($twitter_id))->with('affiliate_id', \request('affiliate_id'));
    }

    $twitter_helper = new TwitterHelper(auth()->user());
    $twitter_account = \App\TwitterAccount::where('twitter_id', $twitter_id)->first();
    $plans = array();

    foreach(array(1, 3, 6, 12) as $months) {
        $twitter_product = new TwitterProduct($twitter_id, $months);
        $plan = new TwitterPlan($twitter_product, 'month', $months);

        if($plan->getStripePlan() != null) {
            array_push($plans, $plan);
        }
    }

    return view('slide.slide-product-purchase')->with('twitter_account', $twitter_account)->with('plans', $plans)->with('twitter_helper', $twitter_helper)->with('store', TwitterStore::where('twitter_id', $twitter_id)->first());
});

Route::get('/product/{id}', function () {
    return view('subscribe-product');
});
 
Route::get('/shop/{twitter_id}', 'ProductController@getShop');

Route::get('/shop/{twitter_id}/{affiliate_id}', function ($twitter_id, $affiliate_id) {
    if (\App\Affiliate::where('id', $affiliate_id)->exists()) {
        return view('subscribe')->with('twitter_id', $twitter_id)->with('descriptions', \App\RoleDesc::where('guild_id', $guild_id)->get())
            ->with('affiliate', \App\Affiliate::where('id', $affiliate_id)->get()[0]);
    } else {
        return view('subscribe')->with('twitter_id', $twitter_id)->with('descriptions', \App\RoleDesc::where('guild_id', $guild_id)->get());
    }
});
Route::post('/get-special-roles', 'ServerController@getSpecialRoles');

Route::post('/process-special-checkout', 'OrderController@specialProcess');

Route::post('/check-prices', 'ProductController@checkProductPrices');

Route::post('/product', 'ProductController@product');

Route::post('/plan', 'ProductController@plan');

Route::post('/update_product_desc', 'ProductController@setProductDescription');