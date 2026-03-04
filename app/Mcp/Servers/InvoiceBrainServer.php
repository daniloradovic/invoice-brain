<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\ClientDetailResource;
use App\Mcp\Resources\ClientListResource;
use App\Mcp\Resources\InvoiceDetailResource;
use App\Mcp\Resources\InvoiceDraftResource;
use App\Mcp\Resources\InvoiceListResource;
use App\Mcp\Resources\InvoiceOutstandingResource;
use App\Mcp\Resources\InvoiceOverdueResource;
use App\Mcp\Resources\ReportSummaryResource;
use App\Mcp\Resources\WorkLogUnbilledClientResource;
use App\Mcp\Resources\WorkLogUnbilledResource;
use App\Mcp\Tools\BulkLogWorkTool;
use App\Mcp\Tools\CreateClientTool;
use App\Mcp\Tools\CreateInvoiceFromWorklogsTool;
use App\Mcp\Tools\CreateInvoiceTool;
use App\Mcp\Tools\LogWorkTool;
use App\Mcp\Tools\UpdateClientNotesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('invoice-brain')]
#[Version('1.0.0')]
#[Instructions('You are an AI billing assistant for Invoice Brain. You have full access to the client list, invoices, work logs, and billing reports. Always check relevant resources before taking action. Amounts are always in cents in raw data — use the formatted fields for display. Never double-invoice unbilled work logs.')]
class InvoiceBrainServer extends Server
{
    protected array $tools = [
        CreateClientTool::class,
        UpdateClientNotesTool::class,
        LogWorkTool::class,
        BulkLogWorkTool::class,
        CreateInvoiceTool::class,
        CreateInvoiceFromWorklogsTool::class,
    ];

    protected array $resources = [
        ClientListResource::class,
        ClientDetailResource::class,
        InvoiceListResource::class,
        InvoiceDetailResource::class,
        InvoiceOutstandingResource::class,
        InvoiceOverdueResource::class,
        InvoiceDraftResource::class,
        WorkLogUnbilledResource::class,
        WorkLogUnbilledClientResource::class,
        ReportSummaryResource::class,
    ];

    protected array $prompts = [
        //
    ];
}
