<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterAccount extends Model
{
    protected $fillable = [
        'user_id', 'twitter_id', 'profile_image', 'name', 'screen_name', 'oauth_token', 'oauth_token_secret', 'description', 'time_zone'
    ];

    public function getPendingFollowRequests() {
        if(auth()->user()->StripeConnect()->express_id == null) return [];

        $request_token = [
            'token'  => $this->oauth_token,
            'secret' => $this->oauth_token_secret,
        ];
        
        \Twitter::reconfig($request_token);
        
        $pending_followers_twitter = \Twitter::getFriendshipsIn()->ids;
        $pending_followers_db = \App\PendingFollowRequest::where('partner_twitter_id', $this->twitter_id)->get();
        $pending_approval = [];
        
        foreach($pending_followers_db as $pending_request) {
            // in DB and in Twitter, not accepted
            if(in_array($pending_request->customer_twitter_id, $pending_followers_twitter)) {
                array_push($pending_approval, $pending_request);
            } 
            // in DB and not in Twitter, accepted
            else {
                $pending_request->delete();
            }
        }

        return $pending_approval;
    }
}
