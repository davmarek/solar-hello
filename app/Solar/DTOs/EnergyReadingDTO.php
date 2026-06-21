<?php

namespace App\Solar\DTOs;

use Carbon\Carbon;

/**
 * Normalized energy reading produced by every provider.
 * SyncService consumes this shape and persists it to energy_readings.
 * Adding fields here (voltage, temperature, etc.) automatically makes them
 * available to every provider that chooses to populate them.
 */
readonly class EnergyReadingDTO
{
    public function __construct(
        public ?float $inverterPowerW,           // AC output of inverter (production providers)
        public ?float $gridPowerW,               // Grid exchange, +export / -import
        public ?float $batteryPowerW,            // Battery flow, +charging / -discharging
        public ?float $batterySocPct,            // Battery state of charge 0–100
        public ?float $energyTodayKwh,
        public ?float $energyTotalKwh,
        public Carbon $recordedAt,               // When this reading was taken
        public array $rawPayload = [],           // Original provider response — kept for debugging
    ) {}

}
