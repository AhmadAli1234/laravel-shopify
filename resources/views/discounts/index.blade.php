@extends('layouts.app')

@section('title', 'Discounts')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Discounts</h1>
        <span class="text-sm text-gray-500">{{ $discounts->total() }} total</span>
    </div>

    @if ($discounts->isEmpty())
        <div class="rounded-md border border-gray-200 bg-white px-4 py-8 text-center text-gray-500">
            No discounts synced yet. Run <code class="px-1 bg-gray-100 rounded">php artisan shopify:sync</code> to backfill from Shopify.
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Code</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Summary</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($discounts as $discount)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $discount->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $discount->code ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $discount->discount_type }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $discount->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($discount->status ?? 'unknown') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $discount->summary }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $discounts->links() }}</div>
    @endif
@endsection
