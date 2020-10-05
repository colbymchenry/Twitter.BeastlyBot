<?php

use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function() {
    if(Auth::check()){
        $stripe_helper = auth()->user()->getStripeHelper();
        $discord_helper = new \App\TwitterHelper(auth()->user());

        return view('dashboard')->with('stripe_helper', $stripe_helper)->with('discord_helper', $discord_helper);
    }else{
        return view('discord_login');
    }
});

Route::get('/', function() {
    return view('site.welcome');
});