<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        $shop = Auth::user();

        $collections = $shop->collections()
            ->orderBy('title')
            ->paginate(25)
            ->withQueryString();

        return view('collections.index', ['collections' => $collections]);
    }
}
