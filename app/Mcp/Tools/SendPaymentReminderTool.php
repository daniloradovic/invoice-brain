<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Mail;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SendPaymentReminderTool extends Tool
{
    protected string $name = 'send_payment_reminder';

    protected string $description = 'Sends a payment reminder email for an overdue or outstanding invoice. Use when the user wants to chase a specific invoice. Appends a reminder note to the invoice notes with timestamp.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->integer()->description('The ID of the invoice to send a reminder for')->required(),
            'message'    => $schema->string()->description('Optional custom message to include in the reminder email')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'message'    => 'nullable|string',
        ]);

        $invoice = Invoice::with('client')->findOrFail($validated['invoice_id']);

        if (! in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue])) {
            return Response::error(
                "Invoice {$invoice->invoice_number} is not eligible for a reminder — status is '{$invoice->status->value}', expected 'sent' or 'overdue'."
            );
        }

        Mail::send(new PaymentReminderMail($invoice, $validated['message'] ?? ''));

        $timestamp = now()->toDateString();
        $existingNotes = $invoice->notes ?? '';
        $reminderNote = "[REMINDER SENT {$timestamp}]";
        $updatedNotes = $existingNotes
            ? "{$reminderNote}\n{$existingNotes}"
            : $reminderNote;

        $invoice->update(['notes' => $updatedNotes]);

        return Response::text(
            "Payment reminder sent to {$invoice->client->email} for invoice {$invoice->invoice_number}. " .
            "Reminder logged in invoice notes."
        );
    }
}
