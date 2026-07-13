@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Products</h1>
        <span class="text-sm text-gray-500">{{ $products->count() }} total</span>
    </div>

    @if ($error)
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $error }}
        </div>
    @endif

    @if ($products->isEmpty() && ! $error)
        <div class="rounded-md border border-gray-200 bg-white px-4 py-8 text-center text-gray-500">
            No products synced yet. Run <code class="px-1 bg-gray-100 rounded">php artisan shopify:sync-products</code> to backfill from Shopify.
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Image</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Vendor</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Price</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Inventory</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Synced</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($products as $product)
                        @php
                            $prices = $product->variants->map(fn ($v) => (float) ($v->price ?? 0));
                            $minPrice = $prices->min() ?? 0;
                            $maxPrice = $prices->max() ?? 0;
                            $inventory = $product->variants->sum(fn ($v) => (int) ($v->inventory_quantity ?? 0));
                            $status = strtolower($product->status ?? 'unknown');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="h-12 w-12 rounded object-cover border border-gray-200">
                                @else
                                    <div class="h-12 w-12 rounded bg-gray-100 border border-gray-200"></div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $product->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $product->vendor ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                @if ($minPrice === $maxPrice)
                                    ${{ number_format($minPrice, 2) }}
                                @else
                                    ${{ number_format($minPrice, 2) }} – ${{ number_format($maxPrice, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $inventory }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $product->synced_at?->diffForHumans() ?? 'never' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
