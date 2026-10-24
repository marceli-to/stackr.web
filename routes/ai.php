<?php

use App\Mcp\Servers\StackrServer;
use Laravel\Mcp\Facades\Mcp;

// Agents sign in with a personal access token from Settings → Agents.
Mcp::web('mcp', StackrServer::class)->middleware(['auth:sanctum', 'throttle:120,1']);
