<?php

namespace App\Console\Commands;

use App\Services\NationalHolidayService;
use Illuminate\Console\Command;

/**
 * Populate the `national_holidays` cache from the Tanggal Merah API.
 *
 * This is the ONLY entry point that performs the outbound HTTP request.
 * Run it manually after deploy, and let the scheduler keep it warm:
 *
 *   php artisan holidays:fetch
 *   php artisan holidays:fetch --year=2026 --year=2027
 */
class FetchNationalHolidays extends Command
{
    protected $signature = 'holidays:fetch
                            {--year=* : Years to fetch (defaults to the current year and the next one)}';

    protected $description = 'Fetch Indonesian national holidays into the local cache table';

    public function handle(): int
    {
        $years = array_map('intval', (array) $this->option('year'));
        $years = array_values(array_filter($years, fn (int $year): bool => $year >= 2000 && $year <= 2100));

        if ($years === []) {
            $years = NationalHolidayService::defaultYears();
        }

        $this->info('Fetching holidays for: '.implode(', ', $years));

        $result = NationalHolidayService::fetchAndStore($years);

        $this->info("Stored/updated {$result['stored']} holiday rows.");

        if ($result['skipped'] !== []) {
            $this->warn('No upstream data for: '.implode(', ', $result['skipped']));
        }

        if ($result['failed'] !== []) {
            $this->error('Failed to fetch: '.implode(', ', $result['failed']));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
