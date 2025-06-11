<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnreadMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_group_id',
        'user_id',
        'message_id',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function chatGroup()
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id', 'chat_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function message()
    {
        return $this->belongsTo(Messages::class, 'message_id', 'message_id');
    }
} 