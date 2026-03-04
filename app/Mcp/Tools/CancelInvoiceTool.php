<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Enums\WorkLogStatus;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CancelInvoiceTool extends Tool
{
    protected string $name = 'cancel_invoice';

    protected string $description = 'Cancels an invoice and records the reason. Also unbills any work logs attached to this invoice so they can be re-invoiced later.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->integer()->description('The ID of the invoice to cancel')->required(),
            'reason'     => $schema->string()->description('Optional reason for cancellation')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'reason'     => 'nullable|string',
        ]);

        $invoice = Invoice::with(['client', 'workLogs'])->findOrFail($validated['invoice_id']);

        if ($invoice->status === InvoiceStatus::Paid) {
            return Response::error(
                "Invoice {$invoice->invoice_number} cannot be cancelled — it has already been paid."
            );
        }

        $unbilledCount = DB::transaction(function () use ($validated, $invoice): int {
            $notes = $invoice->notes ?? '';
            if ($validated['reason'] ?? null) {
                $timestamp = now()->toDateString();
                $cancelNote = "[CANCELLED {$timestamp}]: {$validated['reason']}";
                $notes = $notes ? "{$cancelNote}\n{$notes}" : $cancelNote;
            }

            $invoice->update([
                'status' => InvoiceStatus::Cancelled->value,
                'notes'  => $notes,
            ]);

            $workLogCount = $invoice->workLogs->count();

            $invoice->workLogs()->update([
                'status'     => WorkLogStatus::Unbilled->value,
                'invoice_id' => null,
            ]);

            return $workLogCount;
        });

        $suffix = $unbilledCount > 0
            ? " {$unbilledCount} work " . ($unbilledCount === 1 ? 'log' : 'logs') . " returned to unbilled."
            : '';

        return Response::text(
            "Invoice {$invoice->invoice_number} cancelled.{$suffix}"
        );
    }
}
