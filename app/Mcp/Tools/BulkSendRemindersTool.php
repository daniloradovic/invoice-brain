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

class BulkSendRemindersTool extends Tool
{
    protected string $name = 'bulk_send_reminders';

    protected string $description = 'Sends payment reminders to all overdue invoices matching the threshold. Use when the user wants to chase all late payments at once. Skips invoices that received a reminder in the last 24 hours.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'days_overdue_min' => $schema->integer()->description('Minimum number of days overdue to include. Defaults to 1.')->nullable(),
            'message'          => $schema->string()->description('Optional custom message to include in all reminder emails')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'days_overdue_min' => 'nullable|integer|min:1',
            'message'          => 'nullable|string',
        ]);

        $daysMin = $validated['days_overdue_min'] ?? 1;
        $message = $validated['message'] ?? '';
        $cutoff = now()->subDays($daysMin);

        $invoices = Invoice::with('client')
            ->where('status', InvoiceStatus::Overdue->value)
            ->where('due_at', '<=', $cutoff)
            ->get();

        $sent = 0;
        $skipped = 0;
        $recentThreshold = now()->subHours(24)->toDateString();

        foreach ($invoices as $invoice) {
            if ($invoice->notes && str_contains($invoice->notes, "[REMINDER SENT {$recentThreshold}]")) {
                $skipped++;
                continue;
            }

            Mail::send(new PaymentReminderMail($invoice, $message));

            $timestamp = now()->toDateString();
            $reminderNote = "[REMINDER SENT {$timestamp}]";
            $updatedNotes = $invoice->notes
                ? "{$reminderNote}\n{$invoice->notes}"
                : $reminderNote;

            $invoice->update(['notes' => $updatedNotes]);
            $sent++;
        }

        return Response::text(
            "Sent {$sent} reminder" . ($sent === 1 ? '' : 's') . ". " .
            "Skipped {$skipped} (recently reminded)."
        );
    }
}
