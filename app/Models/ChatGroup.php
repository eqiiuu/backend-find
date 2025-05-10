<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatGroup extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'chat_group_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'chat_group_id',
        'name',
        'capacity',
        'is_private'
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    // Many-to-many relationship with users
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_group_user', 'chat_group_id', 'user_id')
            ->withTimestamps();
    }
    
    public function messages()
    {
        return $this->hasMany(Messages::class);
    }

    // Helper method to check if group is at capacity
    public function isAtCapacity()
    {
        return $this->users()->count() >= $this->capacity;
    }

    // Helper method to add a user to the group
    public function addUser($user_id)
    {
        if ($this->isAtCapacity()) {
            return false;
        }

        $this->users()->attach($user_id);
        return true;
    }

    // Helper method to remove a user from the group
    public function removeUser($user_id)
    {
        $this->users()->detach($user_id);
        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->chat_group_id)) {
                $model->chat_group_id = 'chat_' . Str::random(8);
            }
        });
    }
}
