<?php

use App\AlertHelper;

use App\TwitterHelper;
use App\Subscription;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
#use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\User;
use App\StripeConnect;

Route::get('twitter/login', ['as' => 'twitter.login', function(){
	// your SIGN IN WITH TWITTER  button should point to this route
	$sign_in_twitter = true;
	$force_login = false;

	// Make sure we make this request w/o tokens, overwrite the default values in case of login.
	Twitter::reconfig(['token' => '', 'secret' => '']);
	$token = Twitter::getRequestToken(route('twitter.callback'));

	if (isset($token['oauth_token_secret']))
	{
		$url = Twitter::getAuthorizeURL($token, $sign_in_twitter, $force_login);

		Session::put('oauth_state', 'start');
		Session::put('oauth_request_token', $token['oauth_token']);
		Session::put('oauth_request_token_secret', $token['oauth_token_secret']);

		return Redirect::to($url);
	}

	return Redirect::route('twitter.error');
}]);

Route::get('twitter/callback', ['as' => 'twitter.callback', function() {
	// You should set this route on your Twitter Application settings as the callback
	// https://apps.twitter.com/app/YOUR-APP-ID/settings
	if (Session::has('oauth_request_token'))
	{
		$request_token = [
			'token'  => Session::get('oauth_request_token'),
			'secret' => Session::get('oauth_request_token_secret'),
		];

		Twitter::reconfig($request_token);

		$oauth_verifier = false;

		if (!empty(request('oauth_verifier')))
		{
			$oauth_verifier = request('oauth_verifier');
			// getAccessToken() will reset the token for you
			$token = Twitter::getAccessToken($oauth_verifier);
		}

		if (!isset($token['oauth_token_secret']))
		{
			\App\AlertHelper::alertError('We could not log you in on Twitter.');
			return Redirect::route('twitter.error');
		}

		$credentials = Twitter::getCredentials();

		if (is_object($credentials) && !isset($credentials->error))
		{
			// $credentials contains the Twitter user object with all the info about the user.
			// Add here your own user logic, store profiles, create new users on your tables...you name it!
			// Typically you'll want to store at least, user id, name and access tokens
			// if you want to be able to call the API on behalf of your users.

			// This is also the moment to log in your users if you're using Laravel's Auth class
			// Auth::login($user) should do the trick.

            Session::put('access_token', $token);
            
			if(\App\TwitterAccount::where('twitter_id', $credentials->id)->exists()) {
				$twitter_account = \App\TwitterAccount::where('twitter_id', $credentials->id)->first();
				$twitter_account->name = $credentials->name;
				$twitter_account->screen_name = $credentials->screen_name;
				$twitter_account->oauth_token = $token['oauth_token'];
				$twitter_account->oauth_token_secret = $token['oauth_token_secret'];
				$twitter_account->save();
				$user = User::where('id', $twitter_account->user_id)->first();
				auth()->login($user);
			} else {
				$user = new User();
				$user->save();

				$twitter_account = \App\TwitterAccount::create([
					'user_id' => $user->id,
					'twitter_id' => $credentials->id,
					'name' => $credentials->name,
                    'screen_name' => $credentials->screen_name,
                    'profile_image' => $credentials->profile_image_url_https,
					'oauth_token' => $token['oauth_token'],
					'oauth_token_secret' => $token['oauth_token_secret']
				]);

                auth()->login($user);
            }


            // if the authenticated user does not have a strip account we need to create one for them
            if (! StripeConnect::where('user_id', $user->id)->exists()) {
                // Any time accessing Stripe API this snippet of code must be ran above any preceding API calls
                \Stripe\Stripe::setApiKey(env('STRIPE_CLIENT_SECRET'));
                $stripe_account = \Stripe\Customer::create([
                    "name" => $credentials->name,
                    "metadata" => ['twitter_id' => $credentials->id]
                ]);
                $connect = new StripeConnect(['user_id' => $user->id, 'customer_id' => $stripe_account->id]);
                $connect->save();
                // Add welcome message/email or something
            }
			
			\App\AlertHelper::alertSuccess( 'Congrats! You\'ve successfully signed in!');
			return Redirect::to('/');
		}

		\App\AlertHelper::alertError( 'Crab! Something went wrong while signing you up!');
		return Redirect::route('twitter.error');
	}
}]);

Route::get('twitter/error', ['as' => 'twitter.error', function(){
    // Something went wrong, add your own error handling here
    return Redirect::to('/');
}]);

Route::get('/logout', ['as' => 'logout', function(){
    Session::forget('access_token');
    auth()->logout();
	\App\AlertHelper::alertSuccess('You\'ve successfully logged out!');
	return Redirect::to('/');
}]);


// {
//     "id":2755941776,
//     "id_str":"2755941776",
//     "name":"Colby McHenry",
//     "screen_name":"milkymilkway_",
//     "location":"",
//     "description":"",
//     "url":null,
//     "entities":{
//        "description":{
//           "urls":[
             
//           ]
//        }
//     },
//     "protected":false,
//     "followers_count":4,
//     "friends_count":25,
//     "listed_count":0,
//     "created_at":"Fri Aug 22 18:12:41 +0000 2014",
//     "favourites_count":129,
//     "utc_offset":null,
//     "time_zone":null,
//     "geo_enabled":false,
//     "verified":false,
//     "statuses_count":41,
//     "lang":null,
//     "status":{
//        "created_at":"Sat Sep 19 03:39:55 +0000 2020",
//        "id":1307162479903084545,
//        "id_str":"1307162479903084545",
//        "text":"If I knew earlier that Windscribe #VPN existed, I wouldn't have gotten this tattoo. Oh well. #tweet4data\u2026 https:\/\/t.co\/rFyLrIpQmZ",
//        "truncated":true,
//        "entities":{
//           "hashtags":[
//              {
//                 "text":"VPN",
//                 "indices":[
//                    34,
//                    38
//                 ]
//              },
//              {
//                 "text":"tweet4data",
//                 "indices":[
//                    93,
//                    104
//                 ]
//              }
//           ],
//           "symbols":[
             
//           ],
//           "user_mentions":[
             
//           ],
//           "urls":[
//              {
//                 "url":"https:\/\/t.co\/rFyLrIpQmZ",
//                 "expanded_url":"https:\/\/twitter.com\/i\/web\/status\/1307162479903084545",
//                 "display_url":"twitter.com\/i\/web\/status\/1\u2026",
//                 "indices":[
//                    106,
//                    129
//                 ]
//              }
//           ]
//        },
//        "source":"<a href=\"https:\/\/mobile.twitter.com\" rel=\"nofollow\">Twitter Web App<\/a>",
//        "in_reply_to_status_id":null,
//        "in_reply_to_status_id_str":null,
//        "in_reply_to_user_id":null,
//        "in_reply_to_user_id_str":null,
//        "in_reply_to_screen_name":null,
//        "geo":null,
//        "coordinates":null,
//        "place":null,
//        "contributors":null,
//        "is_quote_status":false,
//        "retweet_count":0,
//        "favorite_count":0,
//        "favorited":false,
//        "retweeted":false,
//        "possibly_sensitive":false,
//        "lang":"en"
//     },
//     "contributors_enabled":false,
//     "is_translator":false,
//     "is_translation_enabled":false,
//     "profile_background_color":"C0DEED",
//     "profile_background_image_url":"http:\/\/abs.twimg.com\/images\/themes\/theme1\/bg.png",
//     "profile_background_image_url_https":"https:\/\/abs.twimg.com\/images\/themes\/theme1\/bg.png",
//     "profile_background_tile":false,
//     "profile_image_url":"http:\/\/abs.twimg.com\/sticky\/default_profile_images\/default_profile_normal.png",
//     "profile_image_url_https":"https:\/\/abs.twimg.com\/sticky\/default_profile_images\/default_profile_normal.png",
//     "profile_link_color":"1DA1F2",
//     "profile_sidebar_border_color":"C0DEED",
//     "profile_sidebar_fill_color":"DDEEF6",
//     "profile_text_color":"333333",
//     "profile_use_background_image":true,
//     "has_extended_profile":false,
//     "default_profile":true,
//     "default_profile_image":true,
//     "following":false,
//     "follow_request_sent":false,
//     "notifications":false,
//     "translator_type":"none",
//     "suspended":false,
//     "needs_phone_verification":false
//  }