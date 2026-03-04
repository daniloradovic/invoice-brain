<?php

namespace App\Mcp\Resources;

use App\Models\Invoice;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class InvoiceDetailResource extends Resource implements HasUriTemplate
{
    protected string $description = 'Single invoice with client, line items, and overdue status';

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('invoices://{id}');
    }

    public function handle(Request $request): Response
    {
        $invoice = Invoice::with(['client', 'lineItems'])->find($request->integer('id'));

        if ($invoice === null) {
            return Response::error('Invoice not found.');
        }

        $lineItems = $invoice->lineItems->map(fn ($item) => [
            'id'          => $item->id,
            'description' => $item->description,
            'quantity'    => (float) $item->quantity,
            'unit_price'  => $item->unit_price,
            'total_cents' => $item->total,
        ])->values()->all();

        $daysOverdue = $invoice->is_overdue ? (int) $invoice->due_at->diffInDays(now()) : 0;

        $summaryParts = ["Invoice {$invoice->invoice_number} for {$invoice->client->name}. " . MoneyService::format($invoice->total) . '.'];
        if ($invoice->is_overdue) {
            $summaryParts[] = "Overdue by {$daysOverdue} days.";
        } else {
            $summaryParts[] = "Status: {$invoice->status->value}.";
        }

        return Response::text(json_encode([
            'data' => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name'    => $invoice->client->name,
                'client_email'   => $invoice->client->email,
                'status'         => $invoice->status->value,
                'total_cents'    => $invoice->total,
                'issued_at'      => $invoice->issued_at?->toDateString(),
                'due_at'         => $invoice->due_at?->toDateString(),
                'paid_at'        => $invoice->paid_at?->toDateString(),
                'is_overdue'     => $invoice->is_overdue,
                'days_overdue'   => $daysOverdue,
                'notes'          => $invoice->notes,
                'line_items'     => $lineItems,
            ],
            'summary' => implode(' ', $summaryParts),
        ]));
    }
}
