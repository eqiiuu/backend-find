<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Communitie;

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
        'image' => 'string',
        'comments' => 'array'
    ];

    protected $appends = ['image_url', 'likes_count', 'is_liked'];

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
        return $this->belongsTo(Communitie::class, 'community_id', 'community_id');
    }

    // Relationship with likes
    public function likes()
    {
        return $this->hasMany(Like::class, 'post_id', 'post_id');
    }

    // Get the number of likes
    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    // Check if the current user has liked the post
    public function getIsLikedAttribute()
    {
        if (!auth()->check()) {
            return false;
        }
        return $this->likes()->where('user_id', auth()->user()->user_id)->exists();
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            // If it's already a full URL, return it
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }

            // Remove any 'public/' prefix if it exists
            $path = str_replace('public/', '', $this->image);
            
            // Log the image path for debugging
            \Log::info('Post image path:', [
                'original' => $this->image,
                'processed' => $path,
                'full_url' => url('storage/' . $path)
            ]);
            
            return url('storage/' . $path);
        }
        return null;
    }
}