<?php

use App\Mcp\Servers\InvoiceBrainServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('invoice-brain', InvoiceBrainServer::class);
