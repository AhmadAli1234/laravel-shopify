<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Maximum number of pages to fetch from Shopify, as a safety cap
     * against runaway pagination on very large catalogs.
     */
    private const MAX_PAGES = 40;

    private const PRODUCTS_QUERY = <<<'GRAPHQL'
        query Products($cursor: String) {
            products(first: 50, after: $cursor) {
                edges {
                    cursor
                    node {
                        title
                        vendor
                        status
                        featuredImage {
                            url
                        }
                        variants(first: 100) {
                            edges {
                                node {
                                    price
                                    inventoryQuantity
                                }
                            }
                        }
                    }
                }
                pageInfo {
                    hasNextPage
                }
            }
        }
        GRAPHQL;

    public function index(Request $request): View
    {
        $shop = Auth::user();

        $products = [];
        $error = null;
        $cursor = null;
        $hasNextPage = true;
        $pages = 0;

        try {
            while ($hasNextPage && $pages < self::MAX_PAGES) {
                $response = $shop->api()->graph(self::PRODUCTS_QUERY, ['cursor' => $cursor]);

                if ($response['errors']) {
                    // On a transport-level failure $response['errors'] is just `true` and
                    // the actual error detail lives in `body` (already extracted) or the
                    // exception; on a GraphQL-level failure $response['errors'] itself is
                    // the error payload from the (successful) HTTP response.
                    $details = $response['errors'] === true
                        ? ($response['body'] ?? $response['exception']?->getMessage() ?? 'Unknown error')
                        : $response['errors'];

                    $error = 'Failed to load products from Shopify: '
                        .(is_string($details) ? $details : json_encode($details));
                    break;
                }

                $connection = $response['body']['data']['products'];

                foreach ($connection['edges'] ?? [] as $edge) {
                    $node = $edge['node'];
                    $variants = collect($node['variants']['edges'] ?? [])
                        ->map(fn ($variantEdge) => $variantEdge['node']);

                    $products[] = [
                        'title' => $node['title'] ?? null,
                        'vendor' => $node['vendor'] ?? null,
                        'status' => strtolower($node['status'] ?? 'unknown'),
                        'image' => $node['featuredImage']['url'] ?? null,
                        'variants' => $variants,
                    ];

                    $cursor = $edge['cursor'];
                }

                $hasNextPage = (bool) ($connection['pageInfo']['hasNextPage'] ?? false);
                $pages++;
            }
        } catch (\Throwable $e) {
            $error = 'Failed to load products from Shopify: '.$e->getMessage();
        }

        return view('products.index', [
            'products' => $products,
            'error' => $error,
        ]);
    }
}
