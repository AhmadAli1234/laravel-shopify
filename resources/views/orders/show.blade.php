@extends('layouts.app')

@section('title', $order->name ?? 'Order')

@section('content')
    <div class="mb-6">
        <a href="{{ route('orders.index', ['shop' => request('shop'), 'host' => request('host')]) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Orders</a>
        <h1 class="text-2xl font-semibold mt-2">{{ $order->name ?? 'Order' }}</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h2 class="text-sm font-medium text-gray-500 mb-2">Customer</h2>
            <p class="text-gray-900">{{ trim(($order->customer?->first_name ?? '').' '.($order->customer?->last_name ?? '')) ?: ($order->email ?? '—') }}</p>
            <p class="text-gray-500 text-sm">{{ $order->email }}</p>
            <p class="text-gray-500 text-sm">{{ $order->phone }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h2 class="text-sm font-medium text-gray-500 mb-2">Shipping Address</h2>
            @php $addr = $order->shipping_address ?? []; @endphp
            <p class="text-gray-700 text-sm">
                {{ $addr['address1'] ?? '' }}<br>
                {{ $addr['city'] ?? '' }} {{ $addr['province'] ?? '' }} {{ $addr['zip'] ?? '' }}<br>
                {{ $addr['country'] ?? '' }}
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h2 class="text-sm font-medium text-gray-500 mb-2">Status</h2>
            <p class="text-gray-700 text-sm">Financial: <strong>{{ ucfirst($order->financial_status ?? 'unknown') }}</strong></p>
            <p class="text-gray-700 text-sm">Fulfillment: <strong>{{ ucfirst($order->fulfillment_status ?? 'unfulfilled') }}</strong></p>
            <p class="text-gray-700 text-sm">Total: <strong>{{ $order->currency }} {{ number_format((float) $order->total_price, 2) }}</strong></p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm mb-6">
        <h2 class="text-sm font-medium text-gray-500 px-4 py-3 border-b border-gray-200">Line Items</h2>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Title</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">SKU</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Qty</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Price</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($order->lineItems as $item)
                    <tr>
                        <td class="px-4 py-2">{{ $item->title }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $item->sku ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $item->quantity }}</td>
                        <td class="px-4 py-2">{{ number_format((float) $item->price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <h2 class="text-sm font-medium text-gray-500 px-4 py-3 border-b border-gray-200">Fulfillments</h2>
            @if ($order->fulfillments->isEmpty())
                <p class="px-4 py-3 text-sm text-gray-500">No fulfillments yet.</p>
            @else
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($order->fulfillments as $fulfillment)
                        <li class="px-4 py-3">
                            <span class="font-medium">{{ ucfirst($fulfillment->status ?? 'unknown') }}</span>
                            @if ($fulfillment->tracking_number)
                                — {{ $fulfillment->tracking_company }} #{{ $fulfillment->tracking_number }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <h2 class="text-sm font-medium text-gray-500 px-4 py-3 border-b border-gray-200">Transactions</h2>
            @if ($order->transactions->isEmpty())
                <p class="px-4 py-3 text-sm text-gray-500">No transactions yet.</p>
            @else
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($order->transactions as $transaction)
                        <li class="px-4 py-3">
                            <span class="font-medium">{{ ucfirst($transaction->kind ?? 'unknown') }}</span>
                            — {{ ucfirst($transaction->status ?? 'unknown') }}
                            ({{ number_format((float) $transaction->amount, 2) }} via {{ $transaction->gateway }})
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
