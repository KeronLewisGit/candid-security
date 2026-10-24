<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\JobPosting;
use App\Notifications\NewApplication;
use App\Services\ApplicantNotifier;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The online application that replaces "download the form and deliver it to
 * our Freeport office". Five short steps, each saved to the server, so it
 * works without JavaScript and can be finished later from the portal.
 */
class ApplyController extends Controller
{
    public const array STEPS = [1 => 'About you', 2 => 'Position', 3 => 'Licences & experience', 4 => 'Documents', 5 => 'Review & send'];

    public function start(Request $request): View|RedirectResponse
    {
        $job = $this->job(input_string($request->query('job')));
        $applicant = $request->user('applicant');

        if (! $applicant instanceof Applicant) {
            return view('pages.apply', ['step' => 1, 'application' => null, 'job' => $job, 'guest' => true] + $this->options());
        }

        // Signed in: resume a draft for the same job, or start a new one pre-filled from the last application.
        $draft = $applicant->applications()->whereNull('submitted_at')->where('job_posting_id', $job?->id)->first();
        if ($draft) {
            return to_route('apply.step', [$draft->reference, $draft->step]);
        }
        if ($job && $applicant->applications()->active()->where('job_posting_id', $job->id)->exists()) {
            return to_route('portal.dashboard')->with('status', 'You have already applied for '.$job->title.'. Track it below.');
        }

        $last = $applicant->applications()->first();
        $application = new Application([
            'first_name' => $applicant->first_name, 'last_name' => $applicant->last_name, 'email' => $applicant->email,
            'phone' => $last->phone ?? $applicant->phone ?? '',
        ]);
        if ($last) {
            $application->fill($last->only(['date_of_birth', 'island', 'settlement', 'citizenship', 'emergency_name', 'emergency_phone', 'drivers_licence', 'firearm_licence', 'guard_licence', 'experience_years', 'last_employer', 'education', 'certifications']));
        }
        $application->applicant_id = $applicant->id;
        $application->job_posting_id = $job?->id;
        $application->position = $job?->position;
        $application->save();

        return to_route('apply.step', [$application->reference, 1]);
    }

    /** Step 1 for a visitor: creates the applicant account and the draft together. */
    public function begin(Request $request): RedirectResponse
    {
        if ($request->user('applicant')) {
            return to_route('apply', ['job' => input_string($request->input('job'))]);
        }

        $request->merge(['email' => strtolower(input_string($request->input('email')))]);
        $data = $request->validate($this->rules(1) + [
            'email' => ['required', 'email:rfc', 'max:255', 'unique:applicants,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'job' => ['nullable', 'string'],
        ], $this->messages() + [
            'email.unique' => 'You already have an applicant account with this email. Sign in to continue your application.',
        ]);

        $applicant = Applicant::create([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'],
            'phone' => $data['phone'], 'password' => $data['password'],
        ]);
        Auth::guard('applicant')->login($applicant);
        $request->session()->regenerate();

        $job = $this->job(input_string($data['job'] ?? ''));
        $application = new Application(array_intersect_key($data, array_flip(['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'island', 'settlement', 'citizenship', 'emergency_name', 'emergency_phone'])));
        $application->applicant_id = $applicant->id;
        $application->job_posting_id = $job?->id;
        $application->position = $job?->position;
        $application->step = 2;
        $application->save();

        return to_route('apply.step', [$application->reference, 2])
            ->with('status', 'Your applicant account is ready and step 1 is saved. You can stop at any point and finish later.');
    }

    public function step(Request $request, string $reference, int $step): View|RedirectResponse
    {
        $application = $this->draft($request, $reference);
        if ($application instanceof RedirectResponse) {
            return $application;
        }
        if ($step < 1 || $step > 5) {
            abort(404);
        }
        if ($step > $application->step) {
            return to_route('apply.step', [$reference, $application->step]);
        }

        return view('pages.apply', [
            'step' => $step,
            'application' => $application->load(['job', 'documents']),
            'job' => $application->job,
            'guest' => false,
        ] + $this->options());
    }

    public function save(Request $request, string $reference, int $step): RedirectResponse
    {
        $application = $this->draft($request, $reference);
        if ($application instanceof RedirectResponse) {
            return $application;
        }
        abort_if($step < 1 || $step > 5 || $step > $application->step, 404);

        $data = $request->validate($this->rules($step), $this->messages());

        match ($step) {
            1 => $application->fill(array_diff_key($data, ['email' => true])),
            2 => $this->savePosition($application, $data),
            3 => $application->fill($data + ['firearm_licence' => false, 'guard_licence' => false, 'certifications' => []]),
            4 => $this->saveDocuments($request, $application),
            default => null,
        };

        if ($step === 5) {
            $application->submit($request->ip());
            $application->load(['documents', 'applicant', 'job']);
            app(ApplicantNotifier::class)->applicationReceived($application);
            Notify::office(new NewApplication($application));

            return to_route('apply.submitted', $reference);
        }

        $application->step = max($application->step, $step + 1);
        $application->save();

        if ($request->input('action') === 'exit') {
            return to_route('portal.dashboard')->with('status', 'Saved. Continue your application whenever you are ready.');
        }

        return to_route('apply.step', [$reference, $step + 1]);
    }

    public function submitted(Request $request, string $reference): View
    {
        $application = $this->own($request, $reference)->load(['documents', 'job']);
        abort_if($application->isDraft(), 404);

        return view('pages.apply-submitted', ['application' => $application]);
    }

    /** @param array<string, mixed> $data */
    private function savePosition(Application $application, array $data): void
    {
        $job = $this->job((string) ($data['job_posting_id'] ?? ''), byId: true);
        $application->job_posting_id = $job?->id;
        $application->position = $job->position ?? $data['position'];
        $application->fill(array_intersect_key($data, array_flip(['work_islands', 'employment_type', 'shifts', 'available_from'])));
    }

    private function saveDocuments(Request $request, Application $application): void
    {
        foreach (array_keys(ApplicationDocument::TYPES) as $type) {
            if ($file = $request->file($type)) {
                // A new upload replaces an earlier one of the same type that is still waiting for review.
                $application->documents()->where('type', $type)->where('status', '!=', 'accepted')->get()->each->delete();
                ApplicationDocument::store($application, $type, $file);
            }
        }
    }

    /** @return array<string, mixed> */
    private function rules(int $step): array
    {
        $islands = array_keys((array) config('candid.islands'));

        return match ($step) {
            1 => [
                'first_name' => ['required', 'string', 'max:80'],
                'last_name' => ['required', 'string', 'max:80'],
                'phone' => ['required', 'string', 'max:40', 'regex:/^(?:\D*\d){7,15}\D*$/'],
                'date_of_birth' => ['required', 'date', 'before:today', 'after:1940-01-01'],
                'island' => ['required', Rule::in($islands)],
                'settlement' => ['required', 'string', 'max:120'],
                'citizenship' => ['required', Rule::in(array_keys(Application::CITIZENSHIP))],
                'emergency_name' => ['required', 'string', 'max:120'],
                'emergency_phone' => ['required', 'string', 'max:40', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            ],
            2 => [
                'job_posting_id' => ['nullable', 'integer', Rule::exists('job_postings', 'id')->where('status', 'open')],
                'position' => ['required_without:job_posting_id', 'nullable', Rule::in(array_keys(Application::POSITIONS))],
                'work_islands' => ['required', 'array', 'min:1'],
                'work_islands.*' => [Rule::in($islands)],
                'employment_type' => ['required', Rule::in(array_keys(Application::EMPLOYMENT))],
                'shifts' => ['required', 'array', 'min:1'],
                'shifts.*' => [Rule::in(array_keys(Application::SHIFTS))],
                'available_from' => ['nullable', 'date', 'after_or_equal:today'],
            ],
            3 => [
                'drivers_licence' => ['required', Rule::in(array_keys(Application::LICENCES))],
                'firearm_licence' => ['boolean'],
                'guard_licence' => ['boolean'],
                'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
                'last_employer' => ['nullable', 'string', 'max:160'],
                'education' => ['required', Rule::in(array_keys(Application::EDUCATION))],
                'certifications' => ['nullable', 'array'],
                'certifications.*' => [Rule::in(array_keys(Application::CERTIFICATIONS))],
                'message' => ['nullable', 'string', 'max:2000'],
            ],
            4 => collect(ApplicationDocument::TYPES)->map(fn () => ApplicationDocument::rules())->all(),
            default => [
                'declaration' => ['accepted'],
                'consent' => ['accepted'],
            ],
        };
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        $kb = ApplicationDocument::MAX_KB / 1024;
        $types = 'PDF, JPG, PNG, HEIC or Word';
        $out = [
            'emergency_phone.regex' => 'Enter a phone number for your emergency contact.',
            'phone.regex' => 'Enter a phone number we can call, e.g. 242-555-0100.',
            'position.required_without' => 'Choose the position you are applying for.',
            'work_islands.required' => 'Choose at least one island you can work on.',
            'shifts.required' => 'Choose at least one shift you can work.',
            'declaration.accepted' => 'Please confirm the information is true.',
            'consent.accepted' => 'Please agree to the checks so we can process your application.',
        ];
        foreach (array_keys(ApplicationDocument::TYPES) as $type) {
            $out[$type.'.max'] = "That file is too large. The limit is {$kb} MB; a phone photo is fine.";
            $out[$type.'.mimes'] = "Upload a {$types} file.";
            $out[$type.'.extensions'] = "Upload a {$types} file.";
            $out[$type.'.uploaded'] = "That file did not upload. The limit is {$kb} MB.";
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return ['steps' => self::STEPS, 'jobs' => JobPosting::live()->orderBy('island')->orderBy('title')->get()];
    }

    private function job(string $value, bool $byId = false): ?JobPosting
    {
        if ($value === '') {
            return null;
        }

        return $byId ? JobPosting::live()->find((int) $value) : JobPosting::live()->where('slug', $value)->first();
    }

    private function own(Request $request, string $reference): Application
    {
        /** @var Applicant $applicant */
        $applicant = $request->user('applicant');

        return $applicant->applications()->where('reference', $reference)->firstOrFail();
    }

    private function draft(Request $request, string $reference): Application|RedirectResponse
    {
        $application = $this->own($request, $reference);
        if (! $application->isDraft()) {
            return to_route('portal.applications.show', $reference)->with('status', 'This application has been sent. Track it here.');
        }

        return $application;
    }
}
