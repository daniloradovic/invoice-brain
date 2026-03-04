<?php

namespace App\Mcp\Resources;

use App\Models\Invoice;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class InvoiceOutstandingResource extends Resource
{
    protected string $uri = 'invoices://outstanding';

    protected string $description = 'All outstanding invoices (status=sent or overdue), sorted by due date ascending';

    public function handle(Request $request): Response
    {
        $invoices = Invoice::with(['client', 'lineItems'])
            ->outstanding()
            ->orderBy('due_at')
            ->get();

        $data = $invoices->map(fn ($invoice) => [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name'    => $invoice->client->name,
            'total_cents'    => $invoice->total,
            'status'         => $invoice->status->value,
            'due_at'         => $invoice->due_at?->toDateString(),
            'days_overdue'   => $invoice->is_overdue ? (int) now()->diffInDays($invoice->due_at) : 0,
        ])->values()->all();

        $count        = count($data);
        $totalCents   = (int) $invoices->sum('total');
        $oldestDays   = collect($data)->max('days_overdue') ?? 0;

        $summary = "{$count} outstanding invoices totalling " . MoneyService::format($totalCents) . ". Oldest is {$oldestDays} days overdue.";

        return Response::text(json_encode([
            'data'    => $data,
            'summary' => $summary,
        ]));
    }
}
