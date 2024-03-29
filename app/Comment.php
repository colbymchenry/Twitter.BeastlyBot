<?php
 
namespace App;
 
use Illuminate\Database\Eloquent\Model;
 
class Comment extends Model
{
    protected $fillable = [
      'ticket_id', 'user_id', 'comment', 'img', 'url', 'read', 'read_support',
    ];
 
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}