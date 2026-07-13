@extends('layouts.app')

@section('title', 'Collections')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Collections</h1>
        <span class="text-sm text-gray-500">{{ $collections->total() }} total</span>
    </div>

    @if ($collections->isEmpty())
        <div class="rounded-md border border-gray-200 bg-white px-4 py-8 text-center text-gray-500">
            No collections synced yet. Run <code class="px-1 bg-gray-100 rounded">php artisan shopify:sync</code> to backfill from Shopify.
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Handle</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Products</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Synced</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($collections as $collection)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $collection->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $collection->handle }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $collection->products_count ?? $collection->products()->count() }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $collection->synced_at?->diffForHumans() ?? 'never' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $collections->links() }}</div>
    @endif
@endsection
