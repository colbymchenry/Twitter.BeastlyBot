<?php

namespace App\Http\Controllers;

use App\AlertHelper;

use App\Order;
use App\Products\TwitterProduct;
use App\Products\ExpressProduct;
use App\Products\ProductMsgException;
use App\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Stripe\Exception\InvalidRequestException;
use App\User;
use App\TwitterStore;
use App\StripeConnect;
use App\Ban;

class OrderController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function setup(Request $request) {
        if (! auth()->user()->hasStripeAccount()) 
            return response()->json(['success' => false, 'msg' => 'You do not have a linked stripe account.']);

        try {
            switch ($request['product_type']) {
                case "twitter":
                    $product = new TwitterProduct($request['twitter_id'], $request['billing_cycle']);
                break;
                case "express":
                    $product = new ExpressProduct($request['billing_cycle'] == '1' ? env('LIVE_MONTHLY_PLAN_ID') : env('LIVE_YEARLY_PLAN_ID'));
                break;
                default:
                    throw new ProductMsgException('Could not find product by that type.');
                break;
            }

            if(auth()->user()->getStripeHelper()->isSubscribedToProduct($product->getStripeProduct()->id)) {
                throw new ProductMsgException('You are already subscribed to that product.');
            }

            $product->checkoutValidate();
        } catch(ProductMsgException $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        } catch(\Stripe\Exception\ApiErrorException $e) {
            if(env('APP_DEBUG')) Log::error($e);
            return response()->json(['success' => false, 'msg' => $e->getError()->message]);
        }

        $stripe_customer = auth()->user()->getStripeHelper()->getCustomerAccount();

        $checkout_data = [
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'subscription_data' => [
                'items' => [[
                    'plan' => $product->getStripePlan()->id,
                    'quantity' => '1'
                ]]
            ],
            'success_url' => $product->getCallbackSuccessURL(),
            'cancel_url' => $product->getCallbackCancelURL(),
            'customer' => $stripe_customer->id,
        ];

        if(!empty($request['coupon_code'])) {
            if(TwitterStore::where('twitter_id', $request['twitter_id'])->exists()) {
                $store = TwitterStore::where('twitter_id', $request['twitter_id'])->first();
                $checkout_data['subscription_data']['coupon'] = $store->user_id . $request['coupon_code'];
            }
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_CLIENT_SECRET'));
        $session = $stripe->checkout->sessions->create($checkout_data);
        
        return response()->json(['success' => true, 'msg' => $session->id]);
    }

    public function checkout() {
        $success = \request('success');

        if($success)
            AlertHelper::alertSuccess('Payment successful!');
        else 
            AlertHelper::alertInfo('Payment cancelled.');

        // If the customer does not exist we have to cancel the order as we won't have a stripe account to charge
        if (! auth()->user()->hasStripeAccount())  {
            AlertHelper::alertError('You do not have a linked stripe account.');
            return redirect('/dashboard');
        }

        try {
            switch (\request('product_type')) {
                case "twitter":
                    $product = new TwitterProduct(\request('twitter_id'), \request('billing_cycle'));
                break;
                case "express":
                    $product = new ExpressProduct(\request('plan_id'));
                break;
                default:
                    throw new ProductMsgException('Could not find product by that type.');
                break;
            }
        } catch(ProductMsgException $e) {
            AlertHelper::alertWarning($e->getMessage());
        } catch(\Stripe\Exception\ApiErrorException $e) {
            if(env('APP_DEBUG')) Log::error($e);
            AlertHelper::alertWarning($e->getError()->message);
        }

        return $success ? $product->checkoutSuccess() : $product->checkoutCancel();
    }

    public function changePlan(Request $request) {
        try {
            switch ($request['product_type']) {
                case "express":
                    $product = new ExpressProduct(null, $request['plan_id']);
                break;
                default:
                    throw new ProductMsgException('Could not find product by that type.');
                break;
            }

            return $product->changePlan($request['plan_id']);
        } catch(ProductMsgException $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        } catch(\Stripe\Exception\ApiErrorException $e) {
            if(env('APP_DEBUG')) Log::error($e);
            return response()->json(['success' => false, 'msg' => $e->getError()->message]);
        }
    }

}
