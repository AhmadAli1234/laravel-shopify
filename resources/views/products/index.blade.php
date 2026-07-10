<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">Products</h1>
            <span class="text-sm text-gray-500">{{ count($products) }} total</span>
        </div>

        @if ($error)
            <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $error }}
            </div>
        @endif

        @if (count($products) === 0 && ! $error)
            <div class="rounded-md border border-gray-200 bg-white px-4 py-8 text-center text-gray-500">
                No products found in this store.
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($products as $product)
                            @php
                                $variants = collect($product['variants'] ?? []);
                                $prices = $variants->map(fn ($v) => (float) ($v['price'] ?? 0));
                                $minPrice = $prices->min();
                                $maxPrice = $prices->max();
                                $inventory = $variants->sum(fn ($v) => (int) ($v['inventoryQuantity'] ?? 0));
                                $image = $product['image'] ?? null;
                                $status = $product['status'] ?? 'unknown';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    @if ($image)
                                        <img src="{{ $image }}" alt="{{ $product['title'] }}" class="h-12 w-12 rounded object-cover border border-gray-200">
                                    @else
                                        <div class="h-12 w-12 rounded bg-gray-100 border border-gray-200"></div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $product['title'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $product['vendor'] ?? '—' }}</td>
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</body>
</html>
