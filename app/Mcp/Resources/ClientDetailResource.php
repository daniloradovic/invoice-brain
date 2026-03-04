<?php

namespace App\Mcp\Resources;

use App\Models\Client;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class ClientDetailResource extends Resource implements HasUriTemplate
{
    protected string $description = 'Single client with all invoices and work logs';

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('clients://{id}');
    }

    public function handle(Request $request): Response
    {
        $client = Client::with(['invoices.lineItems', 'workLogs'])->find($request->integer('id'));

        if ($client === null) {
            return Response::error('Client not found.');
        }

        $invoices = $client->invoices->map(fn ($invoice) => [
            'id'          => $invoice->id,
            'number'      => $invoice->invoice_number,
            'status'      => $invoice->status->value,
            'total_cents' => $invoice->total,
            'due_at'      => $invoice->due_at?->toDateString(),
        ])->values()->all();

        $workLogs = $client->workLogs->map(fn ($log) => [
            'id'          => $log->id,
            'description' => $log->description,
            'hours'       => (float) $log->hours,
            'status'      => $log->status->value,
            'worked_at'   => $log->worked_at?->toDateString(),
        ])->values()->all();

        $outstandingCents = $client->invoices
            ->filter(fn ($invoice) => in_array($invoice->status->value, ['sent', 'overdue']))
            ->sum('total');

        $unbilledHours = round(
            $client->workLogs->filter(fn ($log) => $log->status->value === 'unbilled')->sum('hours'),
            2,
        );

        $summary = "{$client->name}. " . count($invoices) . " invoices (" . MoneyService::format((int) $outstandingCents) . " outstanding). {$unbilledHours} unbilled hours.";

        return Response::text(json_encode([
            'data' => [
                'id'             => $client->id,
                'name'           => $client->name,
                'email'          => $client->email,
                'address'        => $client->address,
                'default_rate'   => $client->default_rate,
                'payment_terms'  => $client->payment_terms,
                'notes'          => $client->notes,
                'invoices'       => $invoices,
                'work_logs'      => $workLogs,
            ],
            'summary' => $summary,
        ]));
    }
}
