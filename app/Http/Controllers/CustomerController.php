<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $shop = Auth::user();

        $customers = $shop->customers()
            ->orderByDesc('total_spent')
            ->paginate(25)
            ->withQueryString();

        return view('customers.index', ['customers' => $customers]);
    }
}
