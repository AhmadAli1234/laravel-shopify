<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="shopify-api-key" content="{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', \Illuminate\Support\Facades\Auth::user()?->name) }}"/>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <title>@yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    @php
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'home', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Products', 'route' => 'products.index', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['label' => 'Orders', 'route' => 'orders.index', 'icon' => 'M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Customers', 'route' => 'customers.index', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4'],
            ['label' => 'Collections', 'route' => 'collections.index', 'icon' => 'M19 11H5m14-4H5m14 8H5m14 4H5'],
            ['label' => 'Discounts', 'route' => 'discounts.index', 'icon' => 'M7 7h.01M7 3h5.586a1 1 0 01.707.293l6.414 6.414a1 1 0 010 1.414l-8.586 8.586a1 1 0 01-1.414 0l-6.414-6.414A1 1 0 013 12.586V7a4 4 0 014-4z'],
        ];
    @endphp

    <div class="flex min-h-screen">
        <aside class="w-56 shrink-0 bg-gray-900 text-gray-300 flex flex-col">
            <div class="h-14 flex items-center px-5 text-white font-semibold text-sm tracking-wide border-b border-gray-800">
                Shop Sync
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route'], ['shop' => request('shop'), 'host' => request('host')]) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors
                           {{ $active ? 'bg-white text-gray-900' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="px-3 py-4 border-t border-gray-800">
                <a href="{{ route('settings.edit') }}" target="_top"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <div class="max-w-6xl mx-auto px-6 py-8">
                @yield('content')
            </div>
        </main>
    </div>

    @if(\Osiset\ShopifyApp\Util::isMPAApplication())
        @include('shopify-app::partials.token_handler')
    @endif
</body>
</html>
