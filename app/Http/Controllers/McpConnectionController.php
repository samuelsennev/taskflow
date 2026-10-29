<?php

namespace App\Http\Controllers;

use App\Enums\McpClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class McpConnectionController extends Controller
{
    /**
     * Disconnect the given MCP client from the user's account.
     */
    public function destroy(Request $request, McpClient $mcpClient): RedirectResponse
    {
        $request->user()->revokeMcpAccess($mcpClient);

        return back();
    }
}
