<?php

namespace App\Mcp\Resources;

use App\Models\Invoice;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class InvoiceDraftResource extends Resource
{
    protected string $uri = 'invoices://draft';

    protected string $description = 'All draft invoices that have not yet been sent';

    public function handle(Request $request): Response
    {
        $invoices = Invoice::with(['client', 'lineItems'])
            ->draft()
            ->get();

        $data = $invoices->map(fn ($invoice) => [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name'    => $invoice->client->name,
            'total_cents'    => $invoice->total,
            'issued_at'      => $invoice->issued_at?->toDateString(),
        ])->values()->all();

        $count = count($data);

        if ($count === 0) {
            $summary = 'No draft invoices.';
        } elseif ($count === 1) {
            $first = $data[0];
            $summary = "1 draft invoice for {$first['client_name']} worth " . MoneyService::format($first['total_cents']) . ". Not yet sent.";
        } else {
            $totalCents = (int) $invoices->sum('total');
            $summary = "{$count} draft invoices totalling " . MoneyService::format($totalCents) . ". Not yet sent.";
        }

        return Response::text(json_encode([
            'data'    => $data,
            'summary' => $summary,
        ]));
    }
}
