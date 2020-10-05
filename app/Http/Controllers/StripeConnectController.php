<?php

namespace App\Http\Controllers;

use App\AlertHelper;

use App\StripeHelper;
use Illuminate\Support\Facades\Log;

class StripeConnectController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function connect()
    {
        $code = \request('code');

        // if there is an error connecting to Stripe, abort and let user know
        if (isset($_GET['error'])) {
            if (env('APP_DEBUG')) Log::error($_GET['error']);
            AlertHelper::alertError('Something went wrong! Open a support ticket.');
            return redirect('/dashboard');
        }

        if ($code == null) return;

        $user = auth()->user();

        $stripe_account = StripeHelper::getAccountFromStripeConnect($code);
        if ($stripe_account->country == 'US' && $user->stripe_express_id == null) {
            $stripe_connect = $user->StripeConnect();
            $stripe_connect->express_id = $stripe_account->id;
            $stripe_connect->save();
            AlertHelper::alertSuccess('Stripe account created! You can now accept payments.');
            return redirect('/dashboard');
        } else {
            AlertHelper::alertError('This is not a US account or you have already connected an account.');
            return redirect('/dashboard');
        }
    }
}
