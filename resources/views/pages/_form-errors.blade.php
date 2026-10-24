@if ($errors->any())
    <div class="rounded-lg border-2 border-[color:var(--critical)] bg-surface px-5 py-4" role="alert">
        <p class="flex items-center gap-2 font-semibold"><x-icon name="alert" class="h-5 w-5 text-[color:var(--critical)]" />Please check {{ $errors->count() === 1 ? 'one answer' : $errors->count().' answers' }}</p>
        <ul class="mt-2 list-disc space-y-1 pl-10 text-[0.9375rem]">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
