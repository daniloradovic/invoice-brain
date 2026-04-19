<?php

use App\Mcp\Servers\InvoiceBrainServer;
use Laravel\Mcp\Facades\Mcp;

if (app()->environment('local')) {
    Mcp::local('invoice-brain', InvoiceBrainServer::class);
} else {
    Mcp::web('/mcp', InvoiceBrainServer::class)
        ->middleware('auth:sanctum');
}
