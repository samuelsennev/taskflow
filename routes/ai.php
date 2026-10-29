<?php

use App\Mcp\Servers\TaskflowServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware('throttle:10,1')->group(function () {
    Mcp::oauthRoutes();
});

Mcp::web('/mcp', TaskflowServer::class)
    ->middleware(['auth:api', 'throttle:120,1'])
    ->name('mcp');
