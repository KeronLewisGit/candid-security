<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * role:recruiter,operations — admins always pass. Deactivated accounts are signed out.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return redirect()->route('login');
        }
        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'This staff account has been deactivated.']);
        }
        abort_unless($roles === [] || $user->hasRole(...$roles), 403, 'Your role does not have access to this page.');

        return $next($request);
    }
}
