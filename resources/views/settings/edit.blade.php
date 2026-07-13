<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopify App Settings</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <div class="max-w-xl mx-auto px-4 py-12">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-2xl font-semibold">Shopify App Settings</h1>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Log out</button>
            </form>
        </div>

        @if (! $configured)
            <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-4 py-3 mb-6">
                No Shopify API credentials configured yet. Enter your app's API key and secret below to get started.
            </p>
        @else
            <p class="text-sm text-gray-500 mb-6">Update your Shopify app credentials below.</p>
        @endif

        @if (session('status'))
            <p class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-4 py-3 mb-6">
                {{ session('status') }}
            </p>
        @endif

        @if ($errors->any())
            <div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-4 py-3 mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-5 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                <input type="text" name="shopify_api_key" value="{{ old('shopify_api_key', $setting->shopify_api_key) }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Secret</label>
                <input type="password" name="shopify_api_secret" placeholder="{{ $setting->shopify_api_secret ? 'Leave blank to keep current secret' : '' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Scopes</label>
                <input type="text" name="shopify_api_scopes" value="{{ old('shopify_api_scopes', $setting->shopify_api_scopes) }}"
                       placeholder="read_products,write_products,read_orders,read_customers"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">App URL</label>
                <input type="text" name="app_url" value="{{ old('app_url', $setting->app_url) }}"
                       placeholder="https://your-tunnel-or-domain.example.com"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                <p class="text-xs text-gray-400 mt-1">The public HTTPS URL this app is reachable at - used to build webhook callback addresses. Update this whenever your tunnel/domain changes.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Connect a store (optional)</label>
                <input type="text" name="shop_domain" placeholder="your-store.myshopify.com"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">If filled in, saving will immediately start the install/OAuth flow for this store.</p>
            </div>

            <button type="submit" class="w-full bg-gray-900 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-gray-800">
                Save
            </button>
        </form>
    </div>
</body>
</html>
