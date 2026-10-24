@props(['stage'])
@php
    [$tone, $icon] = match ($stage) {
        'new' => ['attention', 'dot'],
        'document_check' => ['neutral', 'file'],
        'entrance_exam' => ['neutral', 'clipboard'],
        'drug_test' => ['neutral', 'shield'],
        'interview' => ['neutral', 'users'],
        'background_check' => ['neutral', 'search'],
        'offer' => ['good', 'star'],
        'hired' => ['good', 'check'],
        'rejected', 'withdrawn' => ['muted', 'x'],
        default => ['neutral', 'dot'],
    };
@endphp
<x-chip :tone="$tone" :icon="$icon" {{ $attributes }}>{{ \App\Models\Application::STAGES[$stage] ?? ucfirst($stage) }}</x-chip>
