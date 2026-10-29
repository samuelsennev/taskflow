<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $user->github_id = (string) $gitHubUser->getId();
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }

    /**
     * Return to the login page with an error explaining why GitHub sign-in failed.
     */
    protected function failed(string $message): RedirectResponse
    {
        return to_route('login')->withErrors(['github' => $message]);
    }
}
