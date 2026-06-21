<?php

namespace App\Console\Commands;

use App\Models\EnergyReading;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NormalizeEnergyReadingTimestamps extends Command
{
    protected $signature = 'energy-readings:normalize-timestamps
                            {--dry-run : Preview changes without modifying the database}
                            {--chunk=500 : Number of records to process per batch}';

    protected $description = 'Clamp energy_readings.recorded_at to the nearest 5-minute interval';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        if ($isDryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $total = EnergyReading::count();

        if ($total === 0) {
            $this->info('No energy readings found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} records in chunks of {$chunkSize}...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $skipped = 0;

        EnergyReading::query()
            ->orderBy('id')
            ->chunk($chunkSize, function ($readings) use ($isDryRun, $bar, &$updated, &$skipped) {
                foreach ($readings as $reading) {
                    $original = Carbon::parse($reading->recorded_at);
                    $clamped = $original->copy()->floorMinute(5);

                    if ($original->eq($clamped)) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    if ($this->output->isVeryVerbose()) {
                        $this->newLine();
                        $this->line(sprintf(
                            '  ID %d: %s → %s',
                            $reading->id,
                            $original->toDateTimeString(),
                            $clamped->toDateTimeString(),
                        ));
                    }

                    if (! $isDryRun) {
                        $reading->timestamps = false; // don't touch updated_at
                        $reading->recorded_at = $clamped;
                        $reading->save();
                    }

                    $updated++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total records',    $total],
                ['Already aligned',  $skipped],
                ['Updated'.($isDryRun ? ' (dry run)' : ''), $updated],
            ]
        );

        if ($isDryRun && $updated > 0) {
            $this->warn("Re-run without --dry-run to apply the {$updated} change(s).");
        } elseif ($updated > 0) {
            $this->info("Done — {$updated} record(s) updated successfully.");
        } else {
            $this->info('All timestamps were already aligned to 5-minute intervals.');
        }

        return self::SUCCESS;
    }
}
