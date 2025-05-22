<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Messages extends Model
{
    use HasFactory, Notifiable;
    
    protected $table = 'messages';
    protected $primaryKey = 'message_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'message_id',
        'chat_group_id',
        'user_id',
        'message'
    ];

    protected $with = ['user'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->message_id = 'msg_' . Str::random(8);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function chatGroup()
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id', 'chat_group_id');
    }
}
