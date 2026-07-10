@extends('shopify-app::layouts.default')

@section('styles')
    @include('shopify-app::partials.laravel_skeleton_css')
@endsection

@section('content')
    <ui-title-bar title="Welcome"></ui-title-bar>

    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title m-b-md">
                Laravel &amp; Shopify
            </div>

            <p>Welcome to your Shopify App powered by Laravel.</p>
            <p>&nbsp;</p>
            <p>{{ $shop->name }}</p>
            <p>&nbsp;</p>

            <div class="links">
                <a href="{{ route('products.index', ['shop' => $shop->name, 'host' => request('host')]) }}">View Products</a>
            </div>
        </div>
    </div>
@endsection
