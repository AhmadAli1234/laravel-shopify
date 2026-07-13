<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $shop = Auth::user();

        $products = $shop->products()
            ->with('variants.inventoryLevels')
            ->orderBy('title')
            ->get();

        return view('products.index', [
            'products' => $products,
            'error' => null,
        ]);
    }
}
