<x-layouts.admin title="Office settings">
    <p class="mb-5 max-w-3xl text-ink-2">Phones, addresses and emails shown across the website. Defaults come from candidbahamas.com and The Tribune (2016); confirm them before launch.</p>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="a-card grid max-w-4xl gap-5 p-5 sm:grid-cols-2 sm:p-6">
        @csrf @method('PUT')
        @foreach ($fields as $key => $f)
            <div @class(['sm:col-span-2' => str_ends_with($key, '_address') || $key === 'careers_note'])>
                <label class="a-label" for="{{ $key }}">{{ $f[0] }}</label>
                @if (str_ends_with($key, '_address'))
                    <textarea id="{{ $key }}" name="{{ $key }}" rows="2" class="a-input w-full">{{ old($key, setting($key)) }}</textarea>
                @else
                    <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, setting($key)) }}" class="a-input w-full" @if($key === 'exam_pass_mark') type="number" min="1" max="100" @elseif(str_ends_with($key, '_email')) type="email" @endif>
                @endif
                @if (! empty($f[2]))<p class="mt-1 text-xs text-ink-3">{{ $f[2] }}</p>@endif
            </div>
        @endforeach
        <div class="sm:col-span-2"><button class="a-btn a-btn-primary">Save settings</button></div>
    </form>
</x-layouts.admin>
