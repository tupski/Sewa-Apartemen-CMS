<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Block extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'identifier',
        'content',
        'order',
        'area',
        'status',
        'settings',
        'pages',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'pages' => 'array',
        'order' => 'integer',
    ];

    /**
     * Scope a query to only include active blocks.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive blocks.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to filter blocks by area.
     */
    public function scopeByArea($query, string $area)
    {
        return $query->where('area', $area);
    }

    /**
     * Scope a query to filter blocks by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order blocks by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Check if block should appear on a specific page.
     */
    public function appearsOnPage(int $pageId): bool
    {
        if (empty($this->pages)) {
            return true; // If no pages specified, appears on all pages
        }

        return in_array($pageId, $this->pages);
    }
}
