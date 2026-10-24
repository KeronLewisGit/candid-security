<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ScreeningSlot;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Leads with the one number each role must act on: recruiters see applicants
 * waiting on the document check, operations see client requests that have
 * gone unanswered for more than 24 hours. Admins see both.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $data = ['user' => $user, 'notifyMissing' => Notify::recipients() === [], 'notifyInvalid' => Notify::invalid()];

        if ($user->canRecruit()) {
            $docQueue = Application::query()->submitted()->whereIn('status', ['new', 'document_check']);
            $data += [
                'docWaiting' => (clone $docQueue)->count(),
                'docOldest' => (clone $docQueue)->oldest('submitted_at')->value('submitted_at'),
                'docReady' => (clone $docQueue)->whereNot(fn ($q) => $q->missingDocuments())->count(),
                'stageCounts' => Application::query()->active()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
                'islandCounts' => Application::query()->active()->selectRaw('island, count(*) c')->groupBy('island')->pluck('c', 'island'),
                'recentApplications' => Application::query()->submitted()->with('job')->latest('submitted_at')->take(6)->get(),
                'upcomingSlots' => ScreeningSlot::query()->where('starts_at', '>=', now())->withCount('bookings')->orderBy('starts_at')->take(5)->get(),
                'weekly' => $this->weekly(),
                'drafts' => Application::query()->whereNull('submitted_at')->count(),
            ];
        }

        if ($user->canOperate()) {
            $data += [
                'overdue' => ServiceRequest::query()->overdue()->count(),
                'overdueOldest' => ServiceRequest::query()->overdue()->oldest()->value('created_at'),
                'openRequests' => ServiceRequest::query()->open()->selectRaw('kind, count(*) c')->groupBy('kind')->pluck('c', 'kind'),
                'pickupsSoon' => ServiceRequest::query()->where('kind', 'pickup')->open()->whereBetween('needed_on', [today(), today()->addDays(2)])->orderBy('needed_on')->get(),
                'recentRequests' => ServiceRequest::query()->latest()->take(6)->get(),
            ];
        }

        return view('admin.dashboard', $data);
    }

    /** @return list<array{label: string, value: int}> Applications submitted per week, last 8 weeks. */
    private function weekly(): array
    {
        $start = now()->startOfWeek()->subWeeks(7);
        $dates = Application::query()->where('submitted_at', '>=', $start)->pluck('submitted_at');
        $out = [];
        for ($i = 0; $i < 8; $i++) {
            $from = $start->copy()->addWeeks($i);
            $to = $from->copy()->addWeek();
            $out[] = [
                'label' => $from->format('j M'),
                'value' => $dates->filter(fn (?Carbon $d) => $d && $d->gte($from) && $d->lt($to))->count(),
            ];
        }

        return $out;
    }
}
