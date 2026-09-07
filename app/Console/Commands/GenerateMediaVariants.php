<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\ImageVariantService;
use Illuminate\Console\Command;

class GenerateMediaVariants extends Command
{
    /**
     * Usage:
     *   php artisan media:generate-variants            # backfill missing variants
     *   php artisan media:generate-variants --force    # regenerate everything
     *   php artisan media:generate-variants --id=12    # only one media record
     */
    protected $signature = 'media:generate-variants
                            {--force : Regenerate variants even when they already exist}
                            {--id= : Only process a single media ID}';

    protected $description = 'Generate responsive image variants (400/800/1600 + WebP/AVIF) for media records.';

    public function handle(ImageVariantService $service): int
    {
        $query = Media::query()->where('type', 'image');

        if ($this->option('id')) {
            $query->whereKey((int) $this->option('id'));
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No image media records found.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            // Skip records whose metadata already carries a variants map —
            // makes repeated runs idempotent/cheap.
            $query->where(function ($q) {
                $q->whereNull('metadata')
                    ->orWhereRaw("json_extract(metadata, '$.variants') IS NULL");
            });
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById(50, function ($media) use ($service, &$processed, &$skipped, &$failed) {
            foreach ($media as $item) {
                try {
                    $variants = $service->generateFor($item, (bool) $this->option('force'));

                    if ($variants === []) {
                        $skipped++;

                        continue;
                    }

                    $processed++;
                    $groups = implode('/', array_keys($variants));
                    $this->line("  #{$item->id} ✓ [{$groups}] {$item->directory}/{$item->filename}");
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn("  #{$item->id} ✗ {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $this->info("Done. Generated: {$processed}, skipped (no raster source): {$skipped}, failed: {$failed}, total image records: {$total}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
