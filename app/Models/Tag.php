<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('blog_sidebar');
        });

        static::deleted(function () {
            Cache::forget('blog_sidebar');
        });
    }
}
