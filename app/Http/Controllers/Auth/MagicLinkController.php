<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\MagicLink;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class MagicLinkController extends Controller
{
    /**
     * Show the email entry form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Issue and email a single-use login link.
     *
     * Open registration: an unknown email creates an account. The response is
     * identical whether or not the account already existed (anti-enumeration).
     */
    public function store(Request $request, MagicLink $magicLink): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $email = (string) $validated['email'];

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => Str::before($email, '@')],
        );

        $token = $magicLink->issueFor($email);
        $user->notify(new MagicLinkNotification($email, $token));

        // Neutral confirmation page — identical for known and unknown emails.
        return redirect()->route('login.sent');
    }

    /**
     * Consume a link and open a session.
     */
    public function verify(Request $request, MagicLink $magicLink): RedirectResponse
    {
        $email = (string) $request->query('email', '');
        $token = (string) $request->query('token', '');

        if (! $magicLink->consume($email, $token)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Link wygasł lub został już użyty. Poproś o nowy.',
            ]);
        }

        $user = User::where('email', $email)->firstOrFail();

        if ($user->email_verified_at === null) {
            // Clicking the link proves email ownership.
            $user->forceFill(['email_verified_at' => Carbon::now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * End the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
