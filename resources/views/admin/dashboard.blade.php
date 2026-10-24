@php
    $A = \App\Models\Application::class;
    $recruit = $user->canRecruit();
    $operate = $user->canOperate();
    $lead = ($recruit ? $docWaiting : 0) + ($operate ? $overdue : 0);
@endphp
<x-layouts.admin title="Today">
    @if ($notifyMissing || $notifyInvalid)
        <p class="mb-5 flex items-start gap-2.5 rounded-lg border border-line bg-surface px-5 py-4 text-sm"><x-icon name="alert" class="mt-0.5 h-5 w-5 flex-none text-[color:var(--attention)]" /><span>@if($notifyMissing)Nobody is emailed about new applications or requests. Set <code>CANDID_NOTIFY_EMAIL</code> in <code>.env</code>.@else These entries in <code>CANDID_NOTIFY_EMAIL</code> are not email addresses: {{ implode(', ', $notifyInvalid) }}.@endif</span></p>
    @endif

    {{-- The one number to act on --}}
    <section class="overflow-hidden rounded-xl bg-char-900 text-white on-dark" aria-labelledby="lead-title">
        <div class="grid gap-6 p-6 md:grid-cols-[auto_1fr_auto] md:items-center md:p-8">
            <p class="font-display num text-7xl font-extrabold leading-none md:text-8xl {{ $lead ? 'text-aqua' : 'text-white/70' }}" style="font-variation-settings:'wdth' 118">{{ $lead }}</p>
            <div>
                <h2 id="lead-title" class="text-xl font-bold">
                    @if ($recruit && $operate) {{ $lead === 1 ? 'item needs' : 'items need' }} action now
                    @elseif ($recruit) {{ $docWaiting === 1 ? 'applicant is' : 'applicants are' }} waiting on the document check
                    @else client {{ $overdue === 1 ? 'request has' : 'requests have' }} gone unanswered for over 24 hours
                    @endif
                </h2>
                <ul class="mt-2 space-y-1 text-white/80">
                    @if ($recruit)<li><span class="num font-semibold text-white">{{ $docWaiting }}</span> waiting on document check{{ $docOldest ? ', oldest sent '.\Illuminate\Support\Carbon::parse($docOldest)->diffForHumans() : '' }} · <span class="num">{{ $docReady }}</span> have both documents uploaded</li>@endif
                    @if ($operate)<li><span class="num font-semibold text-white">{{ $overdue }}</span> quote, pickup or message {{ $overdue === 1 ? 'request' : 'requests' }} unanswered over 24h{{ $overdueOldest ? ', oldest '.\Illuminate\Support\Carbon::parse($overdueOldest)->diffForHumans() : '' }}</li>@endif
                </ul>
            </div>
            <div class="flex flex-wrap gap-2 md:flex-col">
                @if ($recruit)<a href="{{ route('admin.applications.index', ['stage' => 'new']) }}" class="btn btn-aqua btn-sm">Check documents<x-icon name="arrow-right" class="h-4 w-4" /></a>@endif
                @if ($operate)<a href="{{ route('admin.requests.index') }}" class="btn {{ $recruit ? 'btn-on-dark' : 'btn-aqua' }} btn-sm">Open the ops board</a>@endif
            </div>
        </div>
        <div class="route opacity-60" aria-hidden="true"></div>
    </section>

    @if ($recruit)
        <h2 class="mono-label mt-10 text-ink-3">Recruitment</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="In the pipeline" :value="$stageCounts->sum()" :href="route('admin.applications.pipeline')" note="Active, all islands" />
            <x-stat-tile label="At exam, drug test or interview" :value="($stageCounts['entrance_exam'] ?? 0) + ($stageCounts['drug_test'] ?? 0) + ($stageCounts['interview'] ?? 0)" :href="route('admin.slots.index')" note="Screening stages" />
            <x-stat-tile label="Offers out" :value="$stageCounts['offer'] ?? 0" :href="route('admin.applications.index', ['stage' => 'offer'])" note="Waiting to start" />
            <x-stat-tile label="Unfinished drafts" :value="$drafts" note="Started online, not sent yet" />
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[1.2fr_1fr]">
            <section class="a-card p-5" aria-labelledby="stages-title">
                <h3 id="stages-title" class="font-semibold">Where applicants are</h3>
                @php($max = max(1, $stageCounts->max() ?? 1))
                <ul class="mt-4 space-y-2.5">
                    @foreach ($A::ACTIVE_STAGES as $stage)
                        @php($n = (int) ($stageCounts[$stage] ?? 0))
                        <li><a href="{{ route('admin.applications.index', ['stage' => $stage]) }}" class="grid min-h-11 grid-cols-[9.5rem_1fr_2.5rem] items-center gap-3 rounded hover:bg-surface-2 md:min-h-8">
                            <span class="text-sm">{{ $A::STAGES[$stage] }}</span>
                            <span class="h-3 rounded-full bg-series-track"><span class="block h-3 rounded-full bg-series" style="width: {{ $n ? max(4, round($n / $max * 100)) : 0 }}%"></span></span>
                            <span class="num text-right text-sm font-semibold">{{ $n }}</span>
                        </a></li>
                    @endforeach
                </ul>
                <p class="mt-4 text-sm text-ink-3">By island: @foreach (candid('islands') as $k => $i)<a class="text-link" href="{{ route('admin.applications.pipeline', ['island' => $k]) }}">{{ $i['name'] }} {{ $islandCounts[$k] ?? 0 }}</a>@if(! $loop->last) · @endif @endforeach</p>
            </section>
            <section class="a-card p-5" aria-labelledby="weekly-title">
                <h3 id="weekly-title" class="font-semibold">Applications sent per week</h3>
                @php($wmax = max(1, max(array_column($weekly, 'value'))))
                <div class="mt-4 flex h-40 items-end gap-2" role="img" aria-label="Applications per week, last 8 weeks: {{ collect($weekly)->map(fn ($w) => $w['label'].' '.$w['value'])->implode(', ') }}">
                    @foreach ($weekly as $w)
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-1">
                            <span class="num text-xs font-semibold">{{ $w['value'] }}</span>
                            <span class="w-full rounded-t bg-series" style="height: {{ $w['value'] ? max(3, round($w['value'] / $wmax * 100)) : 1 }}%"></span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-1 flex gap-2 border-t border-line pt-1">@foreach ($weekly as $w)<span class="flex-1 text-center text-xs text-ink-3">{{ $w['label'] }}</span>@endforeach</div>
            </section>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-2">
            <section class="a-card overflow-hidden">
                <div class="flex items-center justify-between p-5 pb-3"><h3 class="font-semibold">Latest applications</h3><a href="{{ route('admin.applications.index') }}" class="-my-3 -mr-3 inline-flex min-h-11 items-center px-3 text-sm text-link">All</a></div>
                <div class="overflow-x-auto"><table class="data-table"><tbody>
                    @foreach ($recentApplications as $a)
                        <tr><td><a href="{{ route('admin.applications.show', $a) }}" class="font-semibold hover:text-aqua-text">{{ $a->full_name }}</a><span class="block text-xs text-ink-3">{{ $a->roleTitle() }} · {{ $a->islandName() }}</span></td><td><x-status :stage="$a->status" /><span class="mt-1 block text-xs text-ink-3 sm:hidden">{{ $a->submitted_at?->diffForHumans(short: true) }}</span></td><td class="r text-ink-3 max-sm:hidden">{{ $a->submitted_at?->diffForHumans(short: true) }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </section>
            <section class="a-card overflow-hidden">
                <div class="flex items-center justify-between p-5 pb-3"><h3 class="font-semibold">Next screening sessions</h3><a href="{{ route('admin.slots.index') }}" class="-my-3 -mr-3 inline-flex min-h-11 items-center px-3 text-sm text-link">Manage</a></div>
                <div class="overflow-x-auto"><table class="data-table"><tbody>
                    @forelse ($upcomingSlots as $s)
                        <tr><td><span class="font-semibold">{{ $s->kindLabel() }}</span><span class="block text-xs text-ink-3">{{ island_name($s->island) }} · {{ $s->location }}</span></td><td class="num">{{ $s->starts_at->format('D j M, g:i a') }}</td><td class="r num">{{ $s->bookings_count }}/{{ $s->capacity }}</td></tr>
                    @empty
                        <tr><td class="text-ink-3">No sessions scheduled. <a class="text-link" href="{{ route('admin.slots.index') }}">Add one</a> so applicants can book.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        </div>
    @endif

    @if ($operate)
        <h2 class="mono-label mt-10 text-ink-3">Operations</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            @foreach (\App\Models\ServiceRequest::KINDS as $kind => $k)
                <x-stat-tile :label="'Open '.strtolower($k['label'])" :value="(int) ($openRequests[$kind] ?? 0)" :href="route('admin.requests.index', ['kind' => $kind])" note="Not yet closed" />
            @endforeach
        </div>
        <div class="mt-4 grid gap-4 xl:grid-cols-2">
            <section class="a-card overflow-hidden">
                <div class="p-5 pb-3"><h3 class="font-semibold">Armoured pickups, next 48 hours</h3></div>
                <div class="overflow-x-auto"><table class="data-table"><tbody>
                    @forelse ($pickupsSoon as $r)
                        <tr><td><a href="{{ route('admin.requests.show', $r) }}" class="font-semibold hover:text-aqua-text">{{ $r->company ?: $r->name }}</a><span class="block text-xs text-ink-3">{{ $r->detail('pickup_address') }}</span></td><td>{{ $r->needed_on?->format('D j M') }} · {{ $r->option('window', \App\Models\ServiceRequest::WINDOWS) }}</td><td class="r"><x-chip :tone="$r->status === 'new' ? 'attention' : 'neutral'" :icon="$r->status === 'new' ? 'dot' : 'check'">{{ $r->statusLabel() }}</x-chip></td></tr>
                    @empty
                        <tr><td class="text-ink-3">No pickups due.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
            <section class="a-card overflow-hidden">
                <div class="p-5 pb-3"><h3 class="font-semibold">Latest client requests</h3></div>
                <div class="overflow-x-auto"><table class="data-table"><tbody>
                    @foreach ($recentRequests as $r)
                        <tr><td><a href="{{ route('admin.requests.show', $r) }}" class="font-semibold hover:text-aqua-text">{{ $r->company ?: $r->name }}</a><span class="block text-xs text-ink-3">{{ $r->kindLabel() }} · {{ $r->reference }}</span></td><td>@if($r->isOverdue())<x-chip tone="critical" icon="clock">Over 24h</x-chip>@else<x-chip :tone="$r->status === 'new' ? 'attention' : 'neutral'" icon="dot">{{ $r->statusLabel() }}</x-chip>@endif<span class="mt-1 block text-xs text-ink-3 sm:hidden">{{ $r->created_at->diffForHumans(short: true) }}</span></td><td class="r text-ink-3 max-sm:hidden">{{ $r->created_at->diffForHumans(short: true) }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </section>
        </div>
    @endif
</x-layouts.admin>
