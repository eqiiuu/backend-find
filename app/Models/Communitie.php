<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Communitie extends Model
{
    use HasFactory;

    protected $table = 'communities';
    // Jika primary key kamu bukan "id"
    protected $primaryKey = 'community_id';
    public $incrementing = true;
    protected $keyType = 'string';

    // Kolom yang bisa diisi
    protected $fillable = [
        'name',
        'community_id',
        'owner_id',
        'gambar',
        'latitude',
        'longitude',
        'description',
        'anggota',
        'capacity',
        'isMemberPostable'
    ];

    // Tipe data otomatis
    protected $casts = [
        'anggota' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'isMemberPostable' => 'boolean'
    ];

    protected $appends = ['gambar_url'];

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

    public function getGambarUrlAttribute()
    {
        if ($this->gambar) {
            // If it's already a full URL, return it
            if (filter_var($this->gambar, FILTER_VALIDATE_URL)) {
                return $this->gambar;
            }

            // Remove any 'public/' or 'storage/' prefix
            $path = str_replace(['public/', 'storage/'], '', $this->gambar);
            
            // Make sure we use the correct path (communities, not community)
            if (!str_starts_with($path, 'images/communities/')) {
                $path = 'images/communities/' . basename($path);
            }
            
            // Log the final path for debugging
            \Log::info('Community image path in model: ' . $path);
            
            return url('storage/' . $path);
        }
        return null;
    }
}