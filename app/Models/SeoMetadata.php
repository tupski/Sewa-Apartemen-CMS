<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeoMetadata extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'meta_title',
        'meta_description',
        'open_graph',
        'twitter',
        'canonical_url',
        'index_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'open_graph' => 'array',
        'twitter' => 'array',
        'index_status' => 'boolean',
    ];

    /**
     * Get the parent seoable model.
     */
    public function seoable()
    {
        return $this->morphTo();
    }
}
