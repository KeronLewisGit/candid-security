@props(['name', 'label', 'options', 'type' => 'checkbox', 'value' => [], 'required' => false, 'hint' => null, 'cols' => 'sm:grid-cols-2'])
@php
    $current = old($name, $value);
    $current = is_array($current) ? array_map('strval', $current) : [(string) $current];
@endphp
<fieldset {{ $attributes->merge(['class' => '']) }} @error($name) aria-invalid="true" @enderror>
    <legend class="label">{{ $label }}@unless($required) <span class="opt">(optional)</span>@endunless</legend>
    @if ($hint)<p class="hint -mt-1 mb-3">{{ $hint }}</p>@endif
    <div class="grid gap-2.5 {{ $cols }}">
        @foreach ($options as $k => $v)
            <label class="choice"><input type="{{ $type }}" name="{{ $type === 'checkbox' ? $name.'[]' : $name }}" value="{{ $k }}" @checked(in_array((string) $k, $current, true)) @if($type === 'radio' && $required) required @endif><span>{{ $v }}</span></label>
        @endforeach
    </div>
    <x-error :for="$name" />
</fieldset>
