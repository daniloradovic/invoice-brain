<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MarkInvoicePaidTool extends Tool
{
    protected string $name = 'mark_invoice_paid';

    protected string $description = 'Records an invoice as paid. Use when the user confirms payment was received. Updates status to \'paid\' and records the payment date.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->integer()->description('The ID of the invoice to mark as paid')->required(),
            'paid_at'    => $schema->string()->description('Payment date in ISO 8601 format (e.g. 2025-06-15). Defaults to today.')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'paid_at'    => 'nullable|date',
        ]);

        $invoice = Invoice::with('client')->findOrFail($validated['invoice_id']);

        if (! in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue])) {
            return Response::error(
                "Invoice {$invoice->invoice_number} cannot be marked paid — status is '{$invoice->status->value}', expected 'sent' or 'overdue'."
            );
        }

        $paidAt = $validated['paid_at'] ?? now()->toDateString();

        $invoice->load('lineItems');
        $invoice->update([
            'status'  => InvoiceStatus::Paid->value,
            'paid_at' => $paidAt,
        ]);

        $total = MoneyService::format($invoice->total);

        return Response::text(
            "Invoice {$invoice->invoice_number} marked as paid. Amount collected: {$total}. Paid on: {$paidAt}."
        );
    }
}
