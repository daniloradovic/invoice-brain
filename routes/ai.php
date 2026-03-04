<?php

use App\Mcp\Servers\InvoiceBrainServer;
use Laravel\Mcp\Facades\Mcp;

// Local development (stdio, Claude Desktop on same machine):
Mcp::local('invoice-brain', InvoiceBrainServer::class);

// Web transport for production (HTTP/SSE, Railway).
// Uncomment this and comment the local() line above when deploying:
// Mcp::web('/mcp', InvoiceBrainServer::class)
//     ->middleware('auth:sanctum');
