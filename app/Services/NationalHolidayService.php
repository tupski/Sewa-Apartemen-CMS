<?php

namespace App\Services;

use App\Models\NationalHoliday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Indonesian public-holiday cache backed by the Tanggal Merah API.
 *
 * Two hard rules:
 *  1. {@see static::fetchAndStore()} is the ONLY method that talks to the
 *     network, and it is called only from `php artisan holidays:fetch`
 *     (scheduled monthly) — never from a controller, view, or render path.
 *  2. Read paths ({@see static::forMonth()}, {@see static::upcoming()}) query
 *     the `national_holidays` table exclusively.
 */
class NationalHolidayService
{
    public const BASE_URL = 'https://tanggalmerah.upset.dev';

    /**
     * Fetch the given years and upsert them into `national_holidays`.
     *
     * A year the upstream has no data for (HTTP 404 / `success: false`) is
     * skipped, not treated as a hard failure — the API only publishes a year
     * once the government decree exists, so asking for a future year is
     * expected to miss.
     *
     * @param  array<int, int>  $years
     * @return array{stored: int, skipped: array<int, int>, failed: array<int, int>}
     */
    public static function fetchAndStore(array $years): array
    {
        $stored = 0;
        $skipped = [];
        $failed = [];

        foreach (array_unique($years) as $year) {
            $year = (int) $year;

            try {
                $response = Http::timeout(15)
                    ->acceptJson()
                    ->get(static::BASE_URL.'/api/holidays', ['year' => $year]);
            } catch (\Throwable $e) {
                Log::warning('NationalHolidayService: request failed', [
                    'year' => $year,
                    'error' => $e->getMessage(),
                ]);
                $failed[] = $year;

                continue;
            }

            if ($response->status() === 404) {
                $skipped[] = $year;

                continue;
            }

            if (! $response->successful() || $response->json('success') !== true) {
                Log::warning('NationalHolidayService: unexpected response', [
                    'year' => $year,
                    'status' => $response->status(),
                ]);
                $failed[] = $year;

                continue;
            }

            $rows = static::normalize((array) $response->json('data', []), $year);

            if ($rows === []) {
                $skipped[] = $year;

                continue;
            }

            foreach ($rows as $row) {
                NationalHoliday::updateOrCreate(
                    ['date' => $row['date'], 'name' => $row['name']],
                    [
                        'type' => $row['type'],
                        'day' => $row['day'],
                        'year' => $row['year'],
                        'fetched_at' => now(),
                    ]
                );
                $stored++;
            }
        }

        return ['stored' => $stored, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * Holidays inside the given month, keyed by `Y-m-d`.
     *
     * A date carrying both a holiday and a leave entry keeps the holiday one
     * for the calendar cell (a mandatory day off outranks a bridge day).
     *
     * @return Collection<string, NationalHoliday>
     */
    public static function forMonth(int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = NationalHoliday::query()
            ->betweenDates($start, $end)
            ->orderBy('date')
            ->orderBy('type')
            ->get();

        // Explicit fold instead of keyBy(): keyBy() keeps the LAST row per key,
        // which would let a 'leave' entry overwrite the 'holiday' one.
        $byDate = collect();

        foreach ($rows as $row) {
            $key = $row->date->toDateString();

            if (! $byDate->has($key) || $row->isPublicHoliday()) {
                $byDate->put($key, $row);
            }
        }

        return $byDate;
    }

    /**
     * The next few holidays from today onward (inclusive).
     *
     * @return Collection<int, NationalHoliday>
     */
    public static function upcoming(int $limit = 5): Collection
    {
        return NationalHoliday::query()
            ->where('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->limit($limit)
            ->get();
    }

    /**
     * Years that should be kept warm: the current one plus the next.
     *
     * @return array<int, int>
     */
    public static function defaultYears(): array
    {
        $current = (int) now()->year;

        return [$current, $current + 1];
    }

    /**
     * Map the API envelope's `data` array into table rows, dropping anything
     * malformed rather than trusting the payload.
     *
     * @param  array<int, mixed>  $data
     * @return array<int, array{date: string, name: string, type: string, day: ?string, year: int}>
     */
    protected static function normalize(array $data, int $year): array
    {
        $rows = [];

        foreach ($data as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $date = trim((string) ($entry['date'] ?? ''));
            $name = trim((string) ($entry['name'] ?? ''));

            if ($date === '' || $name === '') {
                continue;
            }

            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $date);
            } catch (\Throwable $e) {
                continue;
            }

            if (! $parsed) {
                continue;
            }

            $type = (string) ($entry['type'] ?? NationalHoliday::TYPE_HOLIDAY);
            $type = in_array($type, [NationalHoliday::TYPE_HOLIDAY, NationalHoliday::TYPE_LEAVE], true)
                ? $type
                : NationalHoliday::TYPE_HOLIDAY;

            $day = trim((string) ($entry['day'] ?? ''));

            $rows[] = [
                'date' => $parsed->toDateString(),
                'name' => $name,
                'type' => $type,
                'day' => $day !== '' ? $day : null,
                // Trust the date over the requested year in case the upstream
                // includes a boundary entry.
                'year' => (int) $parsed->year ?: $year,
            ];
        }

        return $rows;
    }
}
