<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Communitie extends Model
{

    protected $table = 'communities';
    // Jika primary key kamu bukan "id"
    protected $primaryKey = 'community_id';
    public $incrementing = true;
    protected $keyType = 'string';

    // Kolom yang bisa diisi
    protected $fillable = [
        'community_id',
        'owner_id',
        'gambar',
        'latitude',
        'longitude',
        'description',
        'anggota',
        'capacity'
    ];

    // Tipe data otomatis
    protected $casts = [
        'anggota' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // (Opsional) Relasi ke model User
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'user_id');
    }

    // (Opsional) Relasi ke model Post
    public function posts()
    {
        return $this->hasMany(Post::class, 'community_id', 'community_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->community_id)) {
                $model->community_id = 'community_' . Str::random(10);
            }
        });
    }
}
