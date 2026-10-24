<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Operations board: quotes, armoured pickups and messages, one column per status. */
class RequestController extends Controller
{
    public function index(Request $request): View
    {
        $kind = input_string($request->query('kind')) ?: 'quote';
        abort_unless(isset(ServiceRequest::KINDS[$kind]), 404);
        $island = input_string($request->query('island'));

        $items = ServiceRequest::query()->where('kind', $kind)
            ->when($island, fn ($q) => $q->where('island', $island))
            ->where(fn ($q) => $q->whereNotIn('status', ServiceRequest::CLOSED)->orWhere('status_changed_at', '>=', now()->subDays(30)))
            ->with('assignee')->orderBy($kind === 'pickup' ? 'needed_on' : 'created_at')->get();

        return view('admin.requests.index', [
            'kind' => $kind,
            'island' => $island,
            'columns' => collect(ServiceRequest::STATUSES[$kind])->map(fn (string $label, string $status) => ['label' => $label, 'items' => $items->where('status', $status)->values()]),
            'openCounts' => ServiceRequest::query()->open()->selectRaw('kind, count(*) c')->groupBy('kind')->pluck('c', 'kind'),
            'overdue' => ServiceRequest::query()->overdue()->where('kind', $kind)->count(),
        ]);
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        return view('admin.requests.show', [
            'item' => $serviceRequest->load('assignee'),
            'users' => User::where('is_active', true)->whereIn('role', ['admin', 'operations'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, ServiceRequest $serviceRequest): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys($serviceRequest->statuses()))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'staff_notes' => ['nullable', 'string', 'max:5000'],
            'needed_on' => ['nullable', 'date'],
        ]);
        foreach (['assigned_to', 'staff_notes', 'needed_on'] as $field) {
            if ($request->has($field)) {
                $serviceRequest->{$field} = $data[$field] ?? null;
            }
        }
        $serviceRequest->save();
        if (! empty($data['status'])) {
            $serviceRequest->moveTo($data['status']);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $serviceRequest->status, 'label' => $serviceRequest->statusLabel()]);
        }

        return back()->with('status', $serviceRequest->reference.' saved.');
    }
}
