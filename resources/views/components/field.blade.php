@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'options' => null, 'placeholder' => null])
@php
    $id = 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $current = old($name, $value);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($errors->has($name) ? str_replace(['.', '[', ']'], '-', $name).'-error' : ''));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@unless($required) <span class="opt">(optional)</span>@endunless</label>
    @if ($options !== null)
        <select id="{{ $id }}" name="{{ $name }}" class="input" @required($required) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror>
            <option value="">Choose…</option>
            @foreach ($options as $k => $v)<option value="{{ $k }}" @selected((string) $current === (string) $k)>{{ $v }}</option>@endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" class="input" rows="4" @required($required) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror {{ $attributes->except('class') }}>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : $current }}" class="input" @required($required) @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror {{ $attributes->except('class') }}>
    @endif
    @if ($hint)<span class="hint" id="{{ $id }}-hint">{{ $hint }}</span>@endif
    <x-error :for="$name" />
</div>
