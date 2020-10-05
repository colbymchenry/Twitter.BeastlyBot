<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterStore extends Model
{
    protected $fillable = ['twitter_id', 'url', 'user_id'];
}
