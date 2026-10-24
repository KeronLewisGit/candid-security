<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Notifications\NewServiceRequest;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Client intake. Three forms, one table: guard-service quotes (an RFP in
 * all but name), armoured-transport pickups and general contact messages.
 */
class RequestController extends Controller
{
    public function quote(Request $request): View
    {
        $service = input_string($request->query('service'));

        return view('pages.quote', ['service' => array_key_exists($service, (array) config('candid.services')) ? $service : '']);
    }

    public function pickup(): View
    {
        return view('pages.pickup');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function store(Request $request, string $kind): RedirectResponse
    {
        // Honeypot: bots fill every field. Pretend it worked and keep nothing.
        if ($request->filled('website')) {
            return to_route('requests.received')->with('received', ['kind' => $kind, 'reference' => 'C'.strtoupper($kind[0]).'-'.now()->format('y').'-0000']);
        }

        $islands = array_keys((array) config('candid.islands'));
        $common = [
            'name' => ['required', 'string', 'max:120'],
            'company' => [$kind === 'pickup' ? 'required' : 'nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => [$kind === 'contact' ? 'nullable' : 'required', 'string', 'max:40', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'island' => [$kind === 'contact' ? 'nullable' : 'required', Rule::in($islands)],
            'message' => [$kind === 'contact' ? 'required' : 'nullable', 'string', 'max:3000'],
        ];

        $specific = match ($kind) {
            'quote' => [
                'service' => ['required', Rule::in(service_keys())],
                'site_type' => ['required', Rule::in(array_keys(ServiceRequest::SITE_TYPES))],
                'coverage' => ['required', Rule::in(array_keys(ServiceRequest::COVERAGE))],
                'posts' => ['required', 'integer', 'min:1', 'max:200'],
                'hours' => ['required', Rule::in(array_keys(ServiceRequest::HOURS))],
                'days' => ['nullable', 'integer', 'min:1', 'max:7'],
                'duration' => ['required', Rule::in(array_keys(ServiceRequest::DURATION))],
                'site_address' => ['nullable', 'string', 'max:255'],
                'needed_on' => ['nullable', 'date', 'after_or_equal:today'],
            ],
            'pickup' => [
                'pickup_address' => ['required', 'string', 'max:255'],
                'destination' => ['required', 'string', 'max:255'],
                'needed_on' => ['required', 'date', 'after_or_equal:today'],
                'window' => ['required', Rule::in(array_keys(ServiceRequest::WINDOWS))],
                'frequency' => ['required', Rule::in(array_keys(ServiceRequest::FREQUENCY))],
                'existing_client' => ['required', Rule::in(['yes', 'no'])],
            ],
            default => [
                'topic' => ['nullable', Rule::in(['general', 'billing', 'complaint', 'other'])],
            ],
        };

        $data = $request->validate($common + $specific, [
            'phone.regex' => 'Enter a phone number we can call, e.g. 242-352-5499.',
            'needed_on.after_or_equal' => 'Choose today or a later date.',
        ]);

        $detailKeys = array_diff(array_keys($specific), ['service', 'needed_on']);
        $serviceRequest = ServiceRequest::create([
            'kind' => $kind,
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'island' => $data['island'] ?? null,
            'service' => $kind === 'pickup' ? 'armored-transport' : ($data['service'] ?? null),
            'needed_on' => $data['needed_on'] ?? null,
            'message' => $data['message'] ?? null,
            'details' => array_intersect_key($data, array_flip($detailKeys)) ?: null,
            'ip' => $request->ip(),
        ]);

        Notify::office(new NewServiceRequest($serviceRequest));

        return to_route('requests.received')->with('received', ['kind' => $kind, 'reference' => $serviceRequest->reference]);
    }

    public function received(Request $request): View|RedirectResponse
    {
        $received = $request->session()->get('received');
        if (! is_array($received)) {
            return to_route('contact');
        }
        $request->session()->keep('received'); // a refresh still shows the reference

        return view('pages.received', $received);
    }
}
