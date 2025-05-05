<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Messages extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'chat_group_id',
        'user_id',
        'message'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function chatGroup()
    {
        return $this->belongsTo(ChatGroup::class);
    }
}
