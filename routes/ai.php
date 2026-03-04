<?php

use App\Mcp\Servers\InvoiceBrainServer;
use Laravel\Mcp\Facades\Mcp;

// Local development (stdio, Claude Desktop on same machine):
// Mcp::local('invoice-brain', InvoiceBrainServer::class);

// Production (HTTP/SSE via Railway):
Mcp::web('/mcp', InvoiceBrainServer::class)
    ->middleware('auth:sanctum');
