<?php

namespace App\Mcp\Resources;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ClientListResource extends Resource
{
    protected string $uri = 'clients://list';

    protected string $description = 'All clients with invoice and billing stats';

    public function handle(Request $request): Response
    {
        $clients = Client::with(['invoices', 'workLogs'])->get();

        $data = $clients->map(function (Client $client): array {
            $overdueCount = $client->invoices
                ->filter(fn ($invoice) => $invoice->status === InvoiceStatus::Sent && $invoice->due_at->isPast())
                ->count();

            return [
                'id'                      => $client->id,
                'name'                    => $client->name,
                'email'                   => $client->email,
                'default_rate_formatted'  => $client->default_rate ? MoneyService::format($client->default_rate) : null,
                'payment_terms'           => $client->payment_terms,
                'open_invoices_count'     => $client->invoices->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->count(),
                'overdue_invoices_count'  => $overdueCount,
                'unbilled_hours'          => (float) $client->workLogs->where('status', 'unbilled')->sum('hours'),
                'notes'                   => $client->notes,
            ];
        })->values()->all();

        $totalOverdue = collect($data)->sum('overdue_invoices_count');
        $totalUnbilledHours = round(collect($data)->sum('unbilled_hours'), 2);
        $count = count($data);

        return Response::text(json_encode([
            'data'    => $data,
            'summary' => "{$count} clients. {$totalOverdue} have overdue invoices. {$totalUnbilledHours} total unbilled hours.",
        ]));
    }
}
