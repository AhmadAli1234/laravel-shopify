<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DiscountController extends Controller
{
    public function index(Request $request): View
    {
        $shop = Auth::user();

        $discounts = $shop->discounts()
            ->orderByDesc('starts_at')
            ->paginate(25)
            ->withQueryString();

        return view('discounts.index', ['discounts' => $discounts]);
    }
}
