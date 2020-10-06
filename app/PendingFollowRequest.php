<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingFollowRequest extends Model
{
    protected $fillable = ['partner_twitter_id', 'customer_twitter_id'];
}
