{{-- Shared contact block for the three intake forms. $requireCompany, $requirePhone, $requireIsland --}}
<div class="grid gap-5 sm:grid-cols-2">
    <x-field name="name" label="Your name" required autocomplete="name" />
    <x-field name="company" label="Company or organisation" :required="$requireCompany ?? false" autocomplete="organization" />
    <x-field name="email" type="email" label="Email" required autocomplete="email" />
    <x-field name="phone" type="tel" label="Phone" :required="$requirePhone ?? true" autocomplete="tel" placeholder="242-555-0100" />
    <x-field name="island" label="Island" :options="collect(candid('islands'))->map(fn ($i) => $i['name'])->all()" :required="$requireIsland ?? true" />
</div>
<div class="absolute -left-[9999px]" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
