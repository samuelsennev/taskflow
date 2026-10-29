<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class McpConnectionController extends Controller
{
    /**
     * Disconnect every MCP client the user has authorized.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->revokeMcpAccess();

        return back();
    }
}
