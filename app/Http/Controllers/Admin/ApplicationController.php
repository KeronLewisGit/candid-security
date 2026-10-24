<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\ApplicantNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicantNotifier $notifier) {}

    public function index(Request $request): View
    {
        $applications = $this->query($request)->with(['job', 'assignee', 'documents'])->latest('submitted_at')->paginate(30)->withQueryString();

        return view('admin.applications.index', [
            'applications' => $applications,
            'stageCounts' => Application::query()->submitted()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'templates' => MessageTemplate::orderBy('sort_order')->get(),
        ] + $this->filterOptions());
    }

    public function pipeline(Request $request): View
    {
        $apps = $this->query($request)->whereIn('status', Application::ACTIVE_STAGES)->with(['job', 'assignee', 'documents', 'bookings.slot'])->orderBy('status_changed_at')->get();

        return view('admin.applications.pipeline', [
            'columns' => collect(Application::ACTIVE_STAGES)->mapWithKeys(fn (string $s) => [$s => $apps->where('status', $s)->values()]),
            'closed' => Application::query()->submitted()->whereIn('status', Application::CLOSED_STAGES)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ] + $this->filterOptions());
    }

    public function show(Application $application): View
    {
        abort_if($application->isDraft(), 404);
        $application->load(['job', 'assignee', 'applicant', 'documents', 'bookings.slot', 'notes.user']);

        return view('admin.applications.show', [
            'application' => $application,
            'users' => User::where('is_active', true)->whereIn('role', ['admin', 'recruiter'])->orderBy('name')->get(['id', 'name']),
            'templates' => MessageTemplate::orderBy('sort_order')->get(),
            'others' => Application::query()->submitted()->where('applicant_id', $application->applicant_id)->whereKeyNot($application->id)->with('job')->latest()->get(),
        ]);
    }

    public function update(Request $request, Application $application): JsonResponse|RedirectResponse
    {
        abort_if($application->isDraft(), 404);
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Application::STAGES))],
            'comment' => ['nullable', 'string', 'max:1000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'exam_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'exam_taken_on' => ['nullable', 'date'],
            'drug_test_result' => ['nullable', Rule::in(array_keys(Application::DRUG_RESULTS))],
            'drug_test_on' => ['nullable', 'date'],
            'background_result' => ['nullable', Rule::in(array_keys(Application::BACKGROUND_RESULTS))],
        ]);
        /** @var User $user */
        $user = $request->user();

        // Screening results: only the fields actually sent, so the kanban PATCH never wipes them.
        $results = array_intersect_key($data, array_flip(['exam_score', 'exam_taken_on', 'drug_test_result', 'drug_test_on', 'background_result']));
        foreach (['assigned_to', 'rating'] as $field) {
            if ($request->has($field)) {
                $results[$field] = $data[$field] ?: null;
            }
        }
        if ($results !== []) {
            $before = $application->only(array_keys($results));
            $application->forceFill($results)->save();
            $changes = [];
            foreach (['exam_score' => 'Exam score', 'drug_test_result' => 'Drug test', 'background_result' => 'Background check'] as $field => $label) {
                if (array_key_exists($field, $results) && $before[$field] != $results[$field] && $results[$field] !== null) {
                    $changes[] = $label.': '.match ($field) {
                        'exam_score' => $results[$field].'%',
                        'drug_test_result' => Application::DRUG_RESULTS[$results[$field]],
                        default => Application::BACKGROUND_RESULTS[$results[$field]],
                    };
                }
            }
            if ($changes) {
                $application->notes()->create(['user_id' => $user->id, 'type' => 'note', 'body' => implode(' · ', $changes)]);
            }
        }

        $moved = ! empty($data['status']) && $application->moveTo($data['status'], $user, $data['comment'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $application->status, 'label' => $application->stageLabel(), 'moved' => $moved]);
        }

        return back()->with('status', $moved ? 'Moved to '.$application->stageLabel().'. The applicant has been notified.' : 'Saved.');
    }

    public function note(Request $request, Application $application): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $application->notes()->create(['user_id' => $request->user()?->id, 'type' => 'note', 'body' => $data['body']]);

        return back()->with('status', 'Note added.');
    }

    public function message(Request $request, Application $application): RedirectResponse
    {
        $data = $this->validateMessage($request);
        /** @var User $user */
        $user = $request->user();
        $application->loadMissing(['applicant', 'job']);

        $this->sendOne($application, $data, $user);

        return back()->with('status', 'Email sent to '.$application->email.'.');
    }

    /** Bulk email: the same template to every selected applicant, optionally moving them on. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $this->validateMessage($request) + $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer']]);
        /** @var User $user */
        $user = $request->user();

        $applications = Application::query()->submitted()->whereIn('id', $data['ids'])->with(['applicant', 'job', 'documents', 'bookings'])->get();
        foreach ($applications as $application) {
            $this->sendOne($application, $data, $user);
        }

        return back()->with('status', 'Email sent to '.$applications->count().' '.str('applicant')->plural($applications->count()).'.');
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->query($request)->with(['job', 'documents'])->latest('submitted_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reference', 'Submitted', 'First name', 'Last name', 'Email', 'Phone', 'Island', 'Settlement', 'Age', 'Position', 'Stage', 'Docs complete', 'Exam %', 'Drug test', 'Background', 'Driver’s licence', 'Firearm licence', 'Experience (yrs)', 'Work islands']);
            foreach ($rows as $a) {
                fputcsv($out, [
                    $a->reference, $a->submitted_at?->format('Y-m-d'), $a->first_name, $a->last_name, $a->email, $a->phone, $a->islandName(), $a->settlement, $a->age(),
                    $a->roleTitle(), $a->stageLabel(), $a->documentsComplete() ? 'Yes' : 'No', $a->exam_score, Application::DRUG_RESULTS[$a->drug_test_result] ?? '',
                    Application::BACKGROUND_RESULTS[$a->background_result] ?? '', Application::LICENCES[$a->drivers_licence] ?? '', $a->firearm_licence ? 'Yes' : 'No',
                    $a->experience_years, implode('; ', array_map('island_name', $a->work_islands ?? [])),
                ]);
            }
            fclose($out);
        }, 'candid-applicants-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{subject: string, body: string, set_status: string|null} */
    private function validateMessage(Request $request): array
    {
        /** @var array{subject: string, body: string, set_status?: string|null} $data */
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:8000'],
            'set_status' => ['nullable', Rule::in(array_keys(Application::STAGES))],
        ]);

        return ['subject' => $data['subject'], 'body' => $data['body'], 'set_status' => $data['set_status'] ?? null];
    }

    /** @param array{subject: string, body: string, set_status: string|null} $data */
    private function sendOne(Application $application, array $data, User $user): void
    {
        $subject = MessageTemplate::render($data['subject'], $application, $user);
        $body = MessageTemplate::render($data['body'], $application, $user);
        $this->notifier->message($application, $subject, $body);
        $application->notes()->create(['user_id' => $user->id, 'type' => 'email', 'is_public' => false, 'body' => $body, 'meta' => ['subject' => $subject]]);
        if ($data['set_status']) {
            $application->moveTo($data['set_status'], $user, 'after the email “'.$subject.'”');
        }
    }

    /** @return Builder<Application> */
    private function query(Request $request): Builder
    {
        return Application::query()->submitted()->filter($request->query());
    }

    /** @return array<string, mixed> */
    private function filterOptions(): array
    {
        return [
            'jobs' => JobPosting::orderBy('title')->get(['id', 'title', 'island']),
            'recruiters' => User::where('is_active', true)->whereIn('role', ['admin', 'recruiter'])->orderBy('name')->get(['id', 'name']),
        ];
    }
}
