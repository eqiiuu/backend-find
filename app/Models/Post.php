<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $primaryKey = 'post_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'post_id',
        'user_id',
        'image',
        'community_id',
        'post_date',
        'comments'
    ];

    protected $casts = [
        'post_date' => 'date',
        'comments' => 'array',
    ];

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
