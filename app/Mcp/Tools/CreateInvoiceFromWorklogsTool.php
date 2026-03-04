<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Enums\WorkLogStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\WorkLog;
use App\Services\InvoiceNumberService;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateInvoiceFromWorklogsTool extends Tool
{
    protected string $name = 'create_invoice_from_worklogs';

    protected string $description = 'Creates an invoice from existing unbilled work log entries. Use when the user wants to invoice a client for work already logged. Marks the work logs as billed. Check worklogs://unbilled/{client_id} first.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id'   => $schema->integer()->description('The ID of the client to invoice')->required(),
            'worklog_ids' => $schema->array()
                ->description('Array of unbilled work log IDs to include in the invoice')
                ->items($schema->integer())
                ->required(),
            'notes' => $schema->string()->description('Optional notes to include on the invoice')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'client_id'     => 'required|integer|exists:clients,id',
            'worklog_ids'   => 'required|array|min:1',
            'worklog_ids.*' => 'required|integer',
            'notes'         => 'nullable|string',
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $worklogIds = $validated['worklog_ids'];

        $workLogs = WorkLog::whereIn('id', $worklogIds)->get();

        $notFound = array_diff($worklogIds, $workLogs->pluck('id')->all());
        if ($notFound) {
            return Response::error('Work log IDs not found: ' . implode(', ', $notFound) . '.');
        }

        $wrongClient = $workLogs->where('client_id', '!=', $client->id);
        if ($wrongClient->isNotEmpty()) {
            return Response::error(
                'Work logs ' . $wrongClient->pluck('id')->implode(', ') .
                " do not belong to client '{$client->name}'."
            );
        }

        $alreadyBilled = $workLogs->where('status', WorkLogStatus::Billed);
        if ($alreadyBilled->isNotEmpty()) {
            return Response::error(
                'Work logs ' . $alreadyBilled->pluck('id')->implode(', ') .
                ' are already billed.'
            );
        }

        $invoice = DB::transaction(function () use ($validated, $client, $workLogs): Invoice {
            $invoiceNumber = app(InvoiceNumberService::class)->generate();
            $issuedAt = now()->toDateString();
            $dueAt = now()->addDays($client->payment_terms ?? 30)->toDateString();

            $invoice = Invoice::create([
                'client_id'      => $client->id,
                'invoice_number' => $invoiceNumber,
                'status'         => InvoiceStatus::Draft->value,
                'issued_at'      => $issuedAt,
                'due_at'         => $dueAt,
                'notes'          => $validated['notes'] ?? null,
            ]);

            foreach ($workLogs as $workLog) {
                InvoiceLineItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $workLog->description,
                    'quantity'    => $workLog->hours,
                    'unit_price'  => $workLog->rate,
                ]);

                $workLog->update([
                    'status'     => WorkLogStatus::Billed->value,
                    'invoice_id' => $invoice->id,
                ]);
            }

            return $invoice;
        });

        $invoice->load('lineItems');
        $total = MoneyService::format($invoice->total);
        $count = $workLogs->count();

        return Response::text(
            "Invoice {$invoice->invoice_number} created for '{$client->name}' from {$count} work " .
            ($count === 1 ? 'log' : 'logs') . ". Total: {$total}. Status: draft."
        );
    }
}
