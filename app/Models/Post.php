<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $primaryKey = 'post_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'post_id',
        'user_id',
        'title',
        'image',
        'description',
        'community_id',
        'post_date',
        'comments'
    ];

    protected $attributes = [
        'comments' => '[]'
    ];

    protected $casts = [
        'post_date' => 'date',
        'comments' => 'array',
        'image' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->post_id)) {
                $model->post_id = Str::random(11);
            }
        });
    }

    // Relationship with comments
    public function comments()
    {
        return $this->hasMany(comments::class, 'post_id', 'post_id')->whereNull('parent_id');
    }

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relasi ke komunitas
    public function community()
    {
        return $this->belongsTo(Community::class, 'community_id', 'community_id');
    }

    
}
