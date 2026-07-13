@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Customers</h1>
        <span class="text-sm text-gray-500">{{ $customers->total() }} total</span>
    </div>

    @if ($customers->isEmpty())
        <div class="rounded-md border border-gray-200 bg-white px-4 py-8 text-center text-gray-500">
            No customers synced yet. Run <code class="px-1 bg-gray-100 rounded">php artisan shopify:sync</code> to backfill from Shopify.
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Orders</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Total Spent</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">State</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ trim($customer->first_name.' '.$customer->last_name) ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $customer->email ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $customer->orders_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ number_format((float) $customer->total_spent, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ ucfirst($customer->state ?? 'unknown') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
@endsection
