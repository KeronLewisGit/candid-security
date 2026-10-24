<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::orderByDesc('is_active')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['account' => new User(['role' => 'recruiter', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        User::create($data);

        return to_route('admin.users.index')->with('status', $data['name'].' can now sign in.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['account' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        if ($user->is($request->user()) && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            return back()->withErrors(['role' => 'You cannot remove your own admin access.']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return to_route('admin.users.index')->with('status', $user->name.' updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');
        $user->update(['is_active' => false]);

        return back()->with('status', $user->name.' can no longer sign in.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?User $user): array
    {
        $request->merge(['email' => strtolower(input_string($request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', Password::min(10)],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
