<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $shop = Auth::user();

        $orders = $shop->orders()
            ->with('customer')
            ->orderByDesc('processed_at')
            ->paginate(25)
            ->withQueryString();

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_if($order->shop_id !== Auth::id(), 404);

        $order->load(['customer', 'lineItems.product', 'fulfillments', 'transactions']);

        return view('orders.show', ['order' => $order]);
    }
}
