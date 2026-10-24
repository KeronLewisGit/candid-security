@php($S = \App\Models\ScreeningSlot::class)
<x-layouts.admin title="Screening sessions">
    <p class="mb-5 max-w-3xl text-ink-2">Add entrance exam sittings, drug-test sessions and interview times. Applicants at that stage see open seats on their island in the portal and book themselves.</p>
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <form method="POST" action="{{ route('admin.slots.store') }}" class="a-card space-y-4 self-start p-5">
            @csrf
            <h2 class="font-semibold">Add a session</h2>
            <div><label class="a-label" for="kind">Type</label><select id="kind" name="kind" class="a-input w-full">@foreach ($S::KINDS as $k => $l)<option value="{{ $k }}" @selected(old('kind') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="a-label" for="island">Island</label><select id="island" name="island" class="a-input w-full">@foreach (candid('islands') as $k => $i)<option value="{{ $k }}" @selected(old('island') === $k)>{{ $i['name'] }}</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="a-label" for="date">Date</label><input id="date" name="date" type="date" value="{{ old('date') }}" min="{{ today()->toDateString() }}" class="a-input w-full" required></div><div><label class="a-label" for="time">Start</label><input id="time" name="time" type="time" value="{{ old('time', '09:00') }}" class="a-input w-full" required></div></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="a-label" for="duration_minutes">Minutes</label><input id="duration_minutes" name="duration_minutes" type="number" value="{{ old('duration_minutes', 60) }}" min="10" class="a-input w-full"></div><div><label class="a-label" for="capacity">Seats</label><input id="capacity" name="capacity" type="number" value="{{ old('capacity', 10) }}" min="1" class="a-input w-full"></div></div>
            <div><label class="a-label" for="location">Location</label><input id="location" name="location" value="{{ old('location', 'Candid Freeport office, Polaris Drive') }}" class="a-input w-full" required></div>
            <div><label class="a-label" for="repeat">Repeat weekly</label><select id="repeat" name="repeat" class="a-input w-full">@foreach ([1 => 'Just once', 2 => '2 weeks', 4 => '4 weeks', 8 => '8 weeks'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div><label class="a-label" for="notes">Notes <span class="font-normal text-ink-3">(internal)</span></label><input id="notes" name="notes" value="{{ old('notes') }}" class="a-input w-full"></div>
            <button class="a-btn a-btn-primary w-full">Add session</button>
        </form>
        <div>
            <nav class="tag-row mb-3" aria-label="Type">
                <a href="{{ route('admin.slots.index') }}" class="tag" @if(! $kind) aria-current="true" @endif>All</a>
                @foreach ($S::KINDS as $k => $l)<a href="{{ route('admin.slots.index', ['kind' => $k]) }}" class="tag" @if($kind === $k) aria-current="true" @endif>{{ $l }}</a>@endforeach
            </nav>
            <div class="a-card overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>When</th><th>Type</th><th>Island · location</th><th class="r">Booked</th><th>Who</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($slots as $slot)
                            <tr>
                                <td class="num whitespace-nowrap font-semibold">{{ $slot->starts_at->format('D j M, g:i a') }}</td>
                                <td>{{ $slot->kindLabel() }}</td>
                                <td>{{ island_name($slot->island) }}<span class="block text-xs text-ink-3">{{ $slot->location }}</span></td>
                                <td class="r num">{{ $slot->bookings_count }}/{{ $slot->capacity }}</td>
                                <td class="text-sm">@foreach ($slot->bookings as $b)<a class="text-link" href="{{ route('admin.applications.show', $b->application_id) }}">{{ $b->application->full_name }}</a>@if(! $loop->last), @endif @endforeach</td>
                                <td class="r">@unless($slot->bookings_count)<form method="POST" action="{{ route('admin.slots.destroy', $slot) }}">@csrf @method('DELETE')<button class="a-btn px-2 text-ink-3 hover:text-[color:var(--critical-text)]" aria-label="Remove session"><x-icon name="trash" class="h-4 w-4" /></button></form>@endunless</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-ink-3">No upcoming sessions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
