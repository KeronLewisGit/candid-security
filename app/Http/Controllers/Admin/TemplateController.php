<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates.index', ['templates' => MessageTemplate::orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.templates.form', ['template' => new MessageTemplate]);
    }

    public function store(Request $request): RedirectResponse
    {
        MessageTemplate::create($this->validated($request));

        return to_route('admin.templates.index')->with('status', 'Template saved.');
    }

    public function edit(MessageTemplate $template): View
    {
        return view('admin.templates.form', ['template' => $template]);
    }

    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $template->update($this->validated($request));

        return to_route('admin.templates.index')->with('status', 'Template updated.');
    }

    public function destroy(MessageTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('status', 'Template deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:8000'],
            'set_status' => ['nullable', Rule::in(array_keys(Application::STAGES))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]) + ['sort_order' => 0];
    }
}
