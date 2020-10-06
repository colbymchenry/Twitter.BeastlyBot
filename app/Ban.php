<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    protected $fillable = ['user_id', 'twitter_id', 'type', 'twitter_store_id', 'guild_id', 'until', 'active', 'reason', 'issued_by'];
    
    protected $casts = [
        'until' => 'datetime'
    ];
}
