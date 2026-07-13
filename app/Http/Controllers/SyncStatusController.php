<?php

namespace App\Http\Controllers;

use App\Services\SyncProgressTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Polled by the "syncing your store..." screen on the dashboard (see
 * resources/views/vendor/shopify-app/home/index.blade.php) while the
 * post-install backfill is running.
 */
class SyncStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $progress = SyncProgressTracker::get(Auth::id());

        return response()->json($progress ?? ['status' => 'completed', 'steps' => []]);
    }
}
