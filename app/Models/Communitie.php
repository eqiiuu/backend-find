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
        'koordinat',
        'description',
        'anggota',
        'capacity'
    ];

    // Tipe data otomatis
    protected $casts = [
        'anggota' => 'array',
        'koordinat' => 'float',
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
}
