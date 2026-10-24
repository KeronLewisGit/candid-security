@props(['code' => null])
{{-- Section label: gold rank chevrons, an optional badge code, then the label. --}}
<p {{ $attributes->merge(['class' => 'eyebrow']) }}><span class="chev" aria-hidden="true"></span>@if($code)<b>{{ $code }}</b>@endif<span>{{ $slot }}</span></p>
