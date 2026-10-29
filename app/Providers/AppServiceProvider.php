<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureDevCommands();
        $this->configurePassport();
    }

    /**
     * Show MCP clients (e.g. Claude) the consent screen when they ask to access an account.
     */
    protected function configurePassport(): void
    {
        Passport::tokensCan([Registrar::OAUTH_SCOPE => 'Acessar suas tasks e projetos']);
        Passport::authorizationView(fn (array $parameters): Response => response()->view('mcp.authorize', $parameters));
    }

    /**
     * Run the Telegram bot alongside `php artisan dev` once it is configured.
     */
    protected function configureDevCommands(): void
    {
        if (filled(config('services.telegram.bot_token'))) {
            DevCommands::artisan('telegram:poll', 'telegram')->blue();
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
