<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'max_places' => 'required|integer|min:1|max:50',
            'wave_link' => 'required|url',
            'site_ouvert' => 'required|boolean',
            'message_global' => 'nullable|string|max:200',
        ]);

        foreach ($request->except('_token') as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Réglages mis à jour.');
    }
}
