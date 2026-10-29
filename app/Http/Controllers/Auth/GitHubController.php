<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Fortify;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GitHubController extends Controller
{
    /**
     * Send the user to GitHub to authorize the application.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Sign the user in with the account GitHub sent back, creating or linking it as needed.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return $this->failed('O login com o GitHub foi cancelado.');
        }

        try {
            $gitHubUser = Socialite::driver('github')->user();
        } catch (InvalidStateException|ClientException) {
            return $this->failed('Não foi possível entrar com o GitHub. Tente novamente.');
        }

        if (! $gitHubUser->getEmail()) {
            return $this->failed('Sua conta do GitHub não tem um e-mail verificado.');
        }

        Auth::login($this->findOrCreateUser($gitHubUser), remember: true);

        $request->session()->regenerate();

        return redirect()->intended(Fortify::redirects('login'));
    }

    /**
     * Find the user linked to the GitHub account, link an existing user with the same email, or create a new one.
     */
    protected function findOrCreateUser(SocialiteUser $gitHubUser): User
    {
        $user = User::firstWhere('github_id', $gitHubUser->getId())
            ?? User::firstWhere('email', $gitHubUser->getEmail())
            ?? new User([
                'name' => $gitHubUser->getName() ?: $gitHubUser->getNickname(),
                'email' => $gitHubUser->getEmail(),
            ]);

        if ($user->exists && $user->github_id === null && $user->email_verified_at === null) {
            $this->revokeExistingAccess($user);
        }

        $user->github_id = (string) $gitHubUser->getId();
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }

    /**
     * Lock out whoever registered an unverified account before GitHub proved who owns its email.
     *
     * Anyone can sign up with someone else's address, so the password, "remember me" token,
     * open sessions and linked Telegram chat of such an account may belong to someone else.
     */
    protected function revokeExistingAccess(User $user): void
    {
        $user->forceFill([
            'password' => null,
            'remember_token' => null,
            'telegram_chat_id' => null,
        ]);

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }

    /**
     * Return to the login page with an error explaining why GitHub sign-in failed.
     */
    protected function failed(string $message): RedirectResponse
    {
        return to_route('login')->withErrors(['github' => $message]);
    }
}
