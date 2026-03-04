<?php

namespace App\Mcp\Resources;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class InvoiceListResource extends Resource
{
    protected string $uri = 'invoices://list';

    protected string $description = 'All invoices with client name, totals, and status';

    public function handle(Request $request): Response
    {
        $invoices = Invoice::with(['client', 'lineItems'])->get();

        $data = $invoices->map(fn ($invoice) => [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name'    => $invoice->client->name,
            'total_cents'    => $invoice->total,
            'status'         => $invoice->status->value,
            'issued_at'      => $invoice->issued_at?->toDateString(),
            'due_at'         => $invoice->due_at?->toDateString(),
        ])->values()->all();

        $draftCount       = $invoices->where('status', InvoiceStatus::Draft)->count();
        $outstandingItems = $invoices->filter(fn ($i) => in_array($i->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue]));
        $outstandingCount = $outstandingItems->count();
        $outstandingCents = (int) $outstandingItems->sum('total');
        $overdueItems     = $invoices->filter(fn ($i) => $i->status === InvoiceStatus::Overdue || ($i->status === InvoiceStatus::Sent && $i->due_at->isPast()));
        $overdueCount     = $overdueItems->count();
        $overdueCents     = (int) $overdueItems->sum('total');
        $paidCount        = $invoices->where('status', InvoiceStatus::Paid)->count();
        $total            = count($data);

        $summary = "{$total} invoices total. {$draftCount} draft, {$outstandingCount} outstanding (" . MoneyService::format($outstandingCents) . "), {$overdueCount} overdue (" . MoneyService::format($overdueCents) . "), {$paidCount} paid.";

        return Response::text(json_encode([
            'data'    => $data,
            'summary' => $summary,
        ]));
    }
}
