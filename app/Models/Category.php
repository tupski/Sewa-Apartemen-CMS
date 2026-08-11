<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('blog_sidebar');
            Cache::forget('dashboard_stats');
        });

        static::deleted(function () {
            Cache::forget('blog_sidebar');
            Cache::forget('dashboard_stats');
        });
    }
}
