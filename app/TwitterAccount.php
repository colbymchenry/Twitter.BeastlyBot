<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterAccount extends Model
{
    protected $fillable = [
        'user_id', 'twitter_id', 'profile_image', 'name', 'screen_name', 'oauth_token', 'oauth_token_secret', 'description'
    ];
}
