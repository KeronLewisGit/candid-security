<x-layouts.site title="Photo credits">
    <x-page-header title="Photo credits" eyebrow="Imagery" code="CC" lead="Photos from Wikimedia Commons are used under the licences below. Photos marked Candid come from candidbahamas.com and will be replaced with originals supplied by Candid." />
    <section class="wrap mt-12">
        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (candid('photos') as $key => $meta)
                @php($c = $credits[$meta['credit']] ?? null)
                <li class="panel overflow-hidden">
                    <x-photo :key="$key" :alt="$meta['alt']" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 50vw, 100vw" class="aspect-[3/2]" />
                    <div class="p-5 text-[0.9375rem]">
                        <p class="font-semibold">{{ $meta['alt'] }}</p>
                        @if ($c)
                            <p class="mt-2 text-ink-2">{{ $meta['ours'] ? 'Candid Security Ltd.' : 'By '.$c['author'] }}@if($c['license_url']) · <a class="text-link" href="{{ $c['license_url'] }}" rel="license noopener" target="_blank">{{ $c['license'] }}</a>@endif</p>
                            <p class="mt-1"><a class="text-link" href="{{ $c['source'] }}" rel="noopener" target="_blank">{{ $meta['ours'] ? 'Source page' : 'Original on Wikimedia Commons' }}</a></p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="measure mt-8 text-sm text-ink-3">Commons photos are illustrative and do not show Candid staff, clients or vehicles. Fonts: Archivo, Work Sans and JetBrains Mono (SIL Open Font License).</p>
    </section>
</x-layouts.site>
