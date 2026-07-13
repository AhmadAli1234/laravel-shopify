<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks the initial post-install backfill's progress per shop, so the
 * dashboard can show a live "syncing your store..." screen instead of
 * silently backfilling in the background. Cache-backed (not a DB table) -
 * this is transient UI state, not data worth persisting long-term.
 */
class SyncProgressTracker
{
    private const TTL_MINUTES = 30;

    public const STEPS = ['products', 'customers', 'collections', 'orders', 'discounts'];

    private static function key(int $shopId): string
    {
        return "sync-progress_{$shopId}";
    }

    public static function start(int $shopId): void
    {
        $steps = [];
        foreach (self::STEPS as $step) {
            $steps[$step] = ['status' => 'pending', 'count' => 0];
        }

        Cache::put(self::key($shopId), [
            'status' => 'running',
            'steps' => $steps,
        ], now()->addMinutes(self::TTL_MINUTES));
    }

    public static function markStepRunning(int $shopId, string $step): void
    {
        self::updateStep($shopId, $step, ['status' => 'running']);
    }

    public static function incrementStepCount(int $shopId, string $step, int $by = 1): void
    {
        $progress = self::get($shopId);

        if (! $progress) {
            return;
        }

        $progress['steps'][$step]['count'] = ($progress['steps'][$step]['count'] ?? 0) + $by;
        Cache::put(self::key($shopId), $progress, now()->addMinutes(self::TTL_MINUTES));
    }

    public static function markStepDone(int $shopId, string $step): void
    {
        $progress = self::updateStep($shopId, $step, ['status' => 'done']);

        if (! $progress) {
            return;
        }

        $allDone = collect($progress['steps'])->every(fn ($s) => $s['status'] === 'done');

        if ($allDone) {
            $progress['status'] = 'completed';
            Cache::put(self::key($shopId), $progress, now()->addMinutes(self::TTL_MINUTES));
        }
    }

    public static function markStepFailed(int $shopId, string $step): void
    {
        self::updateStep($shopId, $step, ['status' => 'failed']);
    }

    private static function updateStep(int $shopId, string $step, array $attributes): ?array
    {
        $progress = self::get($shopId);

        if (! $progress) {
            return null;
        }

        $progress['steps'][$step] = array_merge($progress['steps'][$step] ?? ['count' => 0], $attributes);
        Cache::put(self::key($shopId), $progress, now()->addMinutes(self::TTL_MINUTES));

        return $progress;
    }

    public static function get(int $shopId): ?array
    {
        return Cache::get(self::key($shopId));
    }

    /**
     * True if there's an active (incomplete) sync in progress. False if
     * there's no record at all (never synced, or long since expired) or if
     * it already finished - either way, the dashboard should render normally.
     */
    public static function isRunning(int $shopId): bool
    {
        $progress = self::get($shopId);

        return $progress !== null && $progress['status'] === 'running';
    }
}
