<?php

namespace App\Mcp\Resources;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class InvoiceOverdueResource extends Resource
{
    protected string $uri = 'invoices://overdue';

    protected string $description = 'All overdue invoices (past due date with status sent or overdue) with days_overdue calculated';

    public function handle(Request $request): Response
    {
        $invoices = Invoice::with(['client', 'lineItems'])
            ->where(function ($query): void {
                $query->where('status', InvoiceStatus::Overdue->value)
                    ->orWhere(function ($q): void {
                        $q->where('status', InvoiceStatus::Sent->value)
                            ->where('due_at', '<', now());
                    });
            })
            ->orderBy('due_at')
            ->get();

        $data = $invoices->map(fn ($invoice) => [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name'    => $invoice->client->name,
            'total_cents'    => $invoice->total,
            'status'         => $invoice->status->value,
            'due_at'         => $invoice->due_at?->toDateString(),
            'days_overdue'   => (int) now()->diffInDays($invoice->due_at),
        ])->values()->all();

        $count      = count($data);
        $totalCents = (int) $invoices->sum('total');
        $oldest     = collect($data)->sortByDesc('days_overdue')->first();

        $summaryOldest = $oldest ? ". Oldest: {$oldest['client_name']}, {$oldest['days_overdue']} days." : '.';
        $summary = "{$count} overdue invoices. " . MoneyService::format($totalCents) . " total{$summaryOldest}";

        return Response::text(json_encode([
            'data'    => $data,
            'summary' => $summary,
        ]));
    }
}
