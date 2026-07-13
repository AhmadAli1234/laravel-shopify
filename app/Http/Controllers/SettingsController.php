<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $setting = AppSetting::current();

        return view('settings.edit', [
            'setting' => $setting,
            'configured' => $setting->isConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shopify_api_key' => ['required', 'string'],
            'shopify_api_secret' => ['nullable', 'string'],
            'shopify_api_scopes' => ['required', 'string'],
            'app_url' => ['required', 'url'],
            'shop_domain' => ['nullable', 'string'],
        ]);

        $setting = AppSetting::current();
        $setting->shopify_api_key = $data['shopify_api_key'];
        $setting->shopify_api_scopes = $data['shopify_api_scopes'];
        $setting->app_url = rtrim($data['app_url'], '/');

        // Secret is only overwritten if a new one was actually typed - the
        // form never re-displays the current secret, so a blank submit
        // should keep whatever is already saved rather than wiping it.
        if (filled($data['shopify_api_secret'] ?? null)) {
            $setting->shopify_api_secret = $data['shopify_api_secret'];
        }

        $setting->save();

        if (filled($data['shop_domain'] ?? null)) {
            return redirect()->route('authenticate', ['shop' => $data['shop_domain']]);
        }

        return redirect()->route('settings.edit')->with('status', 'Settings saved.');
    }
}
