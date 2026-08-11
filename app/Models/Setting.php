<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'string',
        'type' => 'string',
    ];

    /**
     * Get the key value as an attribute.
     *
     * @param string $value
     * @return string
     */
    public function getKeyAttribute(string $value): string
    {
        return $value;
    }

    /**
     * Get the value with proper casting based on type.
     *
     * @param string $value
     * @return mixed
     */
    public function getValueAttribute(string $value): mixed
    {
        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
