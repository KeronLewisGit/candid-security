<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScreeningSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Exam sittings, drug-test sessions and interview blocks that applicants book from the portal. */
class SlotController extends Controller
{
    public function index(Request $request): View
    {
        $kind = input_string($request->query('kind'));

        return view('admin.slots.index', [
            'slots' => ScreeningSlot::query()->where('starts_at', '>=', today())
                ->when(array_key_exists($kind, ScreeningSlot::KINDS), fn ($q) => $q->where('kind', $kind))
                ->with('bookings.application')->withCount('bookings')->orderBy('starts_at')->get(),
            'kind' => $kind,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(ScreeningSlot::KINDS))],
            'island' => ['required', Rule::in(array_keys((array) config('candid.islands')))],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'repeat' => ['nullable', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $start = Carbon::parse($data['date'].' '.$data['time']);
        $repeat = (int) ($data['repeat'] ?? 1);
        for ($i = 0; $i < $repeat; $i++) {
            ScreeningSlot::create([
                'kind' => $data['kind'], 'island' => $data['island'], 'starts_at' => $start->copy()->addWeeks($i),
                'duration_minutes' => $data['duration_minutes'], 'location' => $data['location'], 'capacity' => $data['capacity'],
                'notes' => $data['notes'] ?? null, 'created_by' => $request->user()?->id,
            ]);
        }

        return back()->with('status', $repeat > 1 ? "{$repeat} weekly sessions added." : 'Session added.');
    }

    public function destroy(ScreeningSlot $slot): RedirectResponse
    {
        if ($slot->bookings()->exists()) {
            return back()->withErrors(['slot' => 'This session has bookings. Contact those applicants before removing it.']);
        }
        $slot->delete();

        return back()->with('status', 'Session removed.');
    }
}
