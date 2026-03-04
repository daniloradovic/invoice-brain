<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Mail;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SendInvoiceTool extends Tool
{
    protected string $name = 'send_invoice';

    protected string $description = 'Sends an invoice to the client via email with PDF attachment. Only works on draft invoices. Updates status to \'sent\'. Provide an optional custom message to personalise the email.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->integer()->description('The ID of the invoice to send')->required(),
            'message'    => $schema->string()->description('Optional custom message to include in the email')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'message'    => 'nullable|string',
        ]);

        $invoice = Invoice::with(['client', 'lineItems'])->findOrFail($validated['invoice_id']);

        if ($invoice->status !== InvoiceStatus::Draft) {
            return Response::error(
                "Invoice {$invoice->invoice_number} cannot be sent — status is '{$invoice->status->value}', expected 'draft'."
            );
        }

        Mail::send(new InvoiceMail($invoice, $validated['message'] ?? ''));

        $invoice->update(['status' => InvoiceStatus::Sent->value]);

        $total = MoneyService::format($invoice->total);

        return Response::text(
            "Invoice {$invoice->invoice_number} sent to {$invoice->client->email}. " .
            "Total: {$total}. Status updated to 'sent'."
        );
    }
}
