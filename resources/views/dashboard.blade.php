@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $stats = [
            ['label' => 'Products', 'count' => $shop->products()->count(), 'route' => 'products.index', 'bg' => 'bg-blue-600', 'soft' => 'bg-blue-50'],
            ['label' => 'Orders', 'count' => $shop->orders()->count(), 'route' => 'orders.index', 'bg' => 'bg-emerald-600', 'soft' => 'bg-emerald-50'],
            ['label' => 'Customers', 'count' => $shop->customers()->count(), 'route' => 'customers.index', 'bg' => 'bg-purple-600', 'soft' => 'bg-purple-50'],
            ['label' => 'Collections', 'count' => $shop->collections()->count(), 'route' => 'collections.index', 'bg' => 'bg-amber-500', 'soft' => 'bg-amber-50'],
            ['label' => 'Discounts', 'count' => $shop->discounts()->count(), 'route' => 'discounts.index', 'bg' => 'bg-rose-600', 'soft' => 'bg-rose-50'],
        ];
        $revenue = $shop->orders()->sum('total_price');
        $currency = $shop->orders()->value('currency') ?? '';
    @endphp

    <div class="mb-8">
        <h1 class="text-2xl font-semibold">Welcome back, {{ $shop->name }}</h1>
        <p class="text-gray-500 text-sm mt-1">Here's what's synced from your Shopify store.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5 mb-8">
        @foreach ($stats as $stat)
            <a href="{{ route($stat['route'], ['shop' => request('shop'), 'host' => request('host')]) }}"
               class="group relative overflow-hidden rounded-xl {{ $stat['soft'] }} p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
                <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full {{ $stat['bg'] }} opacity-10 group-hover:opacity-20 transition-opacity"></div>
                <div class="text-4xl font-bold text-gray-900 relative">{{ number_format($stat['count']) }}</div>
                <div class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold {{ $stat['bg'] }} text-white px-2.5 py-1 rounded-full">
                    {{ $stat['label'] }}
                </div>
            </a>
        @endforeach
    </div>

    <div class="rounded-xl bg-gradient-to-br from-gray-900 to-gray-700 p-6 shadow-sm text-white">
        <h2 class="text-sm font-medium text-gray-300 mb-1">Total Revenue Synced</h2>
        <div class="text-4xl font-bold">{{ $currency }} {{ number_format((float) $revenue, 2) }}</div>
        <p class="text-xs text-gray-400 mt-2">Across {{ number_format($shop->orders()->count()) }} synced orders.</p>
    </div>
@endsection
