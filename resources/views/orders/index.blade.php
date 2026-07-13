@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Orders</h1>
        <span class="text-sm text-gray-500">{{ $orders->total() }} total</span>
    </div>

    @if ($orders->isEmpty())
        <div class="rounded-md border border-gray-200 bg-white px-4 py-8 text-center text-gray-500">
            No orders synced yet. Run <code class="px-1 bg-gray-100 rounded">php artisan shopify:sync</code> to backfill from Shopify.
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Order</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Total</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Financial</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Fulfillment</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Placed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($orders as $order)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="location.href='{{ route('orders.show', ['order' => $order->id, 'shop' => request('shop'), 'host' => request('host')]) }}'">
                            <td class="px-4 py-3 font-medium">{{ $order->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->customer?->first_name.' '.$order->customer?->last_name ?: ($order->email ?? '—') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $order->currency }} {{ number_format((float) $order->total_price, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ ucfirst($order->financial_status ?? 'unknown') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ ucfirst($order->fulfillment_status ?? 'unfulfilled') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $order->processed_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
@endsection
