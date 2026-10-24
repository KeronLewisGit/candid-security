<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['fields' => Setting::FIELDS]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(Setting::FIELDS)->map(fn ($f, string $key) => match (true) {
            str_ends_with($key, '_email') => ['nullable', 'email', 'max:255'],
            str_ends_with($key, '_url') => ['nullable', 'url', 'max:255'],
            $key === 'exam_pass_mark' => ['required', 'integer', 'min:1', 'max:100'],
            default => ['nullable', 'string', 'max:500'],
        })->all();
        $data = $request->validate($rules);
        foreach (array_keys(Setting::FIELDS) as $key) {
            Setting::put($key, isset($data[$key]) ? trim((string) $data[$key]) : '');
        }

        return back()->with('status', 'Settings saved. The website shows the new details straight away.');
    }
}
