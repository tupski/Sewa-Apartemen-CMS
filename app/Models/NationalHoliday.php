<?php

namespace App\Models;

use App\Services\NationalHolidayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A cached Indonesian public holiday or collective leave day.
 *
 * Rows are written only by {@see NationalHolidayService::fetchAndStore()}.
 * Read paths (the admin dashboard) never call the upstream API.
 */
class NationalHoliday extends Model
{
    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_LEAVE = 'leave';

    protected $fillable = [
        'date',
        'name',
        'type',
        'day',
        'year',
        'fetched_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'fetched_at' => 'datetime',
    ];

    /**
     * `date` is handled by an explicit accessor/mutator instead of the `date`
     * cast on purpose.
     *
     * The built-in cast writes `Y-m-d H:i:s` (the connection's date format) even
     * into a DATE column. MySQL silently truncates that, SQLite does not — so on
     * the SQLite test connection the stored value became `2026-01-01 00:00:00`,
     * which broke exact-match lookups (`updateOrCreate`) and made a month's last
     * day fall outside `whereBetween(..., '…-31')`. Writing a bare `Y-m-d` string
     * keeps both drivers identical; reads still hand back a Carbon instance.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : Carbon::parse($value)->startOfDay(),
            set: fn ($value) => $value === null ? null : Carbon::parse($value)->toDateString(),
        );
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeBetweenDates(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }

    /**
     * True for a mandatory national public holiday (not a collective leave day).
     */
    public function isPublicHoliday(): bool
    {
        return $this->type === self::TYPE_HOLIDAY;
    }

    /**
     * Translated label for the holiday type.
     */
    public function typeLabel(): string
    {
        return $this->isPublicHoliday()
            ? __('holiday.type_holiday')
            : __('holiday.type_leave');
    }
}
