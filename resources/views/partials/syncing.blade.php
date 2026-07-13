<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="shopify-api-key" content="{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shop->name) }}"/>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <title>Setting up your store...</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen flex items-center justify-center text-white">
    <div class="w-full max-w-md px-6">
        <div class="text-center mb-8">
            <div class="mx-auto mb-4 h-14 w-14 rounded-2xl bg-white/10 flex items-center justify-center animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <h1 class="text-xl font-semibold">Setting up {{ $shop->name }}</h1>
            <p class="text-gray-400 text-sm mt-1">Pulling in your store's data - this only happens once.</p>
        </div>

        <div class="bg-white/5 border border-white/10 rounded-xl p-6 backdrop-blur">
            <div class="mb-5">
                <div class="flex justify-between text-xs text-gray-400 mb-1.5">
                    <span id="sync-overall-label">Starting...</span>
                    <span id="sync-overall-percent">0%</span>
                </div>
                <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                    <div id="sync-overall-bar" class="h-full bg-gradient-to-r from-emerald-400 to-emerald-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <ul id="sync-steps" class="space-y-3">
                @foreach (\App\Services\SyncProgressTracker::STEPS as $step)
                    <li data-step="{{ $step }}" class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="step-icon h-5 w-5 rounded-full border-2 border-white/20 flex items-center justify-center shrink-0"></span>
                            <span class="capitalize text-gray-200">{{ $step }}</span>
                        </div>
                        <span class="step-count text-xs text-gray-500">-</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <script>
        const STEP_LABELS = { products: 'Products', customers: 'Customers', collections: 'Collections', orders: 'Orders', discounts: 'Discounts' };
        const totalSteps = Object.keys(STEP_LABELS).length;

        function renderIcon(status) {
            if (status === 'done') {
                return '<svg class="h-3 w-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';
            }
            if (status === 'running') {
                return '<span class="block h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>';
            }
            if (status === 'failed') {
                return '<span class="block h-2 w-2 rounded-full bg-red-400"></span>';
            }
            return '';
        }

        async function poll() {
            let data;
            try {
                const res = await fetch('{{ route('sync-status') }}', { headers: { 'Accept': 'application/json' } });
                data = await res.json();
            } catch (e) {
                setTimeout(poll, 2000);
                return;
            }

            const steps = data.steps || {};
            let done = 0;

            Object.entries(steps).forEach(([name, step]) => {
                const li = document.querySelector(`li[data-step="${name}"]`);
                if (!li) return;

                const icon = li.querySelector('.step-icon');
                const count = li.querySelector('.step-count');

                icon.innerHTML = renderIcon(step.status);
                icon.classList.toggle('border-emerald-400', step.status === 'done' || step.status === 'running');
                icon.classList.toggle('border-red-400', step.status === 'failed');

                count.textContent = step.status === 'pending' ? '-' : `${step.count} synced`;

                if (step.status === 'done' || step.status === 'failed') done++;
            });

            const percent = Math.round((done / totalSteps) * 100);
            document.getElementById('sync-overall-bar').style.width = percent + '%';
            document.getElementById('sync-overall-percent').textContent = percent + '%';
            document.getElementById('sync-overall-label').textContent =
                data.status === 'completed' ? 'All done!' : `Syncing ${done}/${totalSteps} complete`;

            if (data.status === 'completed') {
                setTimeout(() => window.location.reload(), 900);
                return;
            }

            setTimeout(poll, 1200);
        }

        document.addEventListener('DOMContentLoaded', poll);
    </script>
</body>
</html>
