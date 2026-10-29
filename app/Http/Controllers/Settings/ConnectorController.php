<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectorController extends Controller
{
    /**
     * Show the services that can be connected to the user's account.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('settings/connectors', [
            'telegram' => [
                'is_available' => filled(config('services.telegram.bot_username')),
                'is_connected' => $request->user()->hasTelegramConnected(),
            ],
        ]);
    }
}
