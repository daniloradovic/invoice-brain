<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AddLineItemTool extends Tool
{
    protected string $name = 'add_line_item';

    protected string $description = 'Adds a line item to an existing draft invoice. Use when the user wants to add an item to an invoice that hasn\'t been sent yet.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id'  => $schema->integer()->description('The ID of the draft invoice to add a line item to')->required(),
            'description' => $schema->string()->description('Description of the line item')->required(),
            'quantity'    => $schema->number()->description('Quantity (e.g. hours, days, units)')->required(),
            'unit_price'  => $schema->integer()->description('Price per unit in cents')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'invoice_id'  => 'required|integer|exists:invoices,id',
            'description' => 'required|string',
            'quantity'    => 'required|numeric|min:0.01',
            'unit_price'  => 'required|integer|min:1',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        if ($invoice->status !== InvoiceStatus::Draft) {
            return Response::error(
                "Invoice {$invoice->invoice_number} is not a draft — cannot add line items to a '{$invoice->status->value}' invoice."
            );
        }

        InvoiceLineItem::create([
            'invoice_id'  => $invoice->id,
            'description' => $validated['description'],
            'quantity'    => $validated['quantity'],
            'unit_price'  => $validated['unit_price'],
        ]);

        $invoice->load('lineItems');
        $newTotal = MoneyService::format($invoice->total);

        return Response::text(
            "Line item '{$validated['description']}' added to invoice {$invoice->invoice_number}. " .
            "Updated total: {$newTotal}."
        );
    }
}
