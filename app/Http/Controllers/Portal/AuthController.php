<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::guard('applicant')->attempt(['email' => strtolower($credentials['email']), 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'That email and password do not match an applicant account.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        /** @var Applicant $applicant */
        $applicant = Auth::guard('applicant')->user();
        $applicant->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function showRegister(): View
    {
        return view('portal.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(input_string($request->input('email')))]);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:applicants,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], ['email.unique' => 'An applicant account already uses this email. Sign in or reset your password.']);

        $applicant = Applicant::create($data);
        Auth::guard('applicant')->login($applicant);
        $request->session()->regenerate();

        return to_route('portal.dashboard')->with('status', 'Welcome, '.$applicant->first_name.'. Your applicant account is ready.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('applicant')->logout();
        // Staff and applicants share one session cookie: signing out of one must not sign out the other.
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return to_route('careers');
    }

    public function showForgot(): View
    {
        return view('portal.forgot');
    }

    public function sendReset(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::broker('applicants')->sendResetLink(['email' => strtolower(input_string($request->input('email')))]);

        // Same reply whether or not the address exists.
        return back()->with('status', 'If an applicant account uses that email, a reset link is on its way.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('portal.reset', ['token' => $token, 'email' => input_string($request->query('email'))]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(8)]]);
        $status = Password::broker('applicants')->reset(
            ['email' => strtolower(input_string($request->input('email'))), 'password' => $request->input('password'), 'password_confirmation' => $request->input('password_confirmation'), 'token' => $request->input('token')],
            function (Applicant $applicant, string $password) {
                $applicant->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($applicant));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? to_route('portal.login')->with('status', 'Your password has been reset. Please sign in.')
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }
}
