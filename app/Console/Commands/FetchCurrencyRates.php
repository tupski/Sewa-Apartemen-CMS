<?php

namespace App\Console\Commands;

use App\Services\CurrencyRateService;
use Illuminate\Console\Command;

class FetchCurrencyRates extends Command
{
    protected $signature   = 'currency:fetch';
    protected $description = 'Fetch currency rates from API and cache (runs 4x/day via scheduler)';

    public function handle(): int
    {
        $ok = CurrencyRateService::fetchAndStore();
        $this->line($ok ? '<info>Currency rates updated.</info>' : '<error>Failed to fetch rates.</error>');
        return $ok ? 0 : 1;
    }
}
