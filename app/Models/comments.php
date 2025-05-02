<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class comments extends Model
{
    use HasFactory;

    protected $primaryKey = 'comment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'comment_id',
        'post_id',
        'user_id',
        'parent_id',
        'content'
    ];

    // Relationship with Post
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'post_id');
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relationship for replies
    public function replies()
    {
        return $this->hasMany(comments::class, 'parent_id', 'comment_id');
    }

    // Relationship for parent comment
    public function parent()
    {
        return $this->belongsTo(comments::class, 'parent_id', 'comment_id');
    }
}
