{{-- Dispatcher: shows the live sync-progress screen while the post-install
     backfill is still running (see App\Services\SyncProgressTracker and
     App\Listeners\Shopify\BackfillShopDataOnInstall), otherwise the normal
     dashboard. Deliberately not using a conditional @extends here - Blade
     doesn't handle that reliably - each branch renders its own complete,
     independent view instead. --}}
@if (\App\Services\SyncProgressTracker::isRunning($shop->id))
    {!! view('partials.syncing', ['shop' => $shop])->render() !!}
@else
    {!! view('dashboard', ['shop' => $shop])->render() !!}
@endif
