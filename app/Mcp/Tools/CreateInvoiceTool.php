<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Services\InvoiceNumberService;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateInvoiceTool extends Tool
{
    protected string $name = 'create_invoice';

    protected string $description = 'Creates a new invoice with explicit line items. Use for ad-hoc invoices where the user specifies items directly. For invoicing existing unbilled work logs, use create_invoice_from_worklogs instead.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id'  => $schema->integer()->description('The ID of the client to invoice')->required(),
            'line_items' => $schema->array()
                ->description('The line items for this invoice')
                ->items($schema->object([
                    'description' => $schema->string()->description('Description of the item')->required(),
                    'quantity'    => $schema->number()->description('Quantity (e.g. hours, days, units)')->required(),
                    'unit_price'  => $schema->integer()->description('Price per unit in cents')->required(),
                ]))
                ->required(),
            'issued_at' => $schema->string()->description('Invoice issue date (ISO 8601). Defaults to today.')->nullable(),
            'notes'     => $schema->string()->description('Optional notes to include on the invoice')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'client_id'                    => 'required|integer|exists:clients,id',
            'line_items'                   => 'required|array|min:1',
            'line_items.*.description'     => 'required|string',
            'line_items.*.quantity'        => 'required|numeric|min:0.01',
            'line_items.*.unit_price'      => 'required|integer|min:1',
            'issued_at'                    => 'nullable|date',
            'notes'                        => 'nullable|string',
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $issuedAt = $validated['issued_at'] ?? now()->toDateString();
        $dueAt = now()->parse($issuedAt)->addDays($client->payment_terms ?? 30)->toDateString();

        $invoice = DB::transaction(function () use ($validated, $client, $issuedAt, $dueAt): Invoice {
            $invoiceNumber = app(InvoiceNumberService::class)->generate();

            $invoice = Invoice::create([
                'client_id'      => $client->id,
                'invoice_number' => $invoiceNumber,
                'status'         => InvoiceStatus::Draft->value,
                'issued_at'      => $issuedAt,
                'due_at'         => $dueAt,
                'notes'          => $validated['notes'] ?? null,
            ]);

            foreach ($validated['line_items'] as $item) {
                InvoiceLineItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                ]);
            }

            return $invoice;
        });

        $invoice->load('lineItems');
        $total = MoneyService::format($invoice->total);

        return Response::text(
            "Invoice {$invoice->invoice_number} created for '{$client->name}'. " .
            "Total: {$total}. Due: {$dueAt}. Status: draft."
        );
    }
}
