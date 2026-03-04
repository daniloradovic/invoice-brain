<?php

namespace App\Mcp\Resources;

use App\Models\Client;
use App\Models\WorkLog;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class WorkLogUnbilledClientResource extends Resource implements HasUriTemplate
{
    protected string $description = 'Unbilled work logs for a single client';

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('worklogs://unbilled/{client_id}');
    }

    public function handle(Request $request): Response
    {
        $clientId = $request->integer('client_id');
        $client = Client::find($clientId);

        if ($client === null) {
            return Response::error('Client not found.');
        }

        $logs = WorkLog::unbilled()->forClient($clientId)->get();

        $data = $logs->map(fn ($log) => [
            'id'          => $log->id,
            'description' => $log->description,
            'hours'       => (float) $log->hours,
            'rate'        => $log->rate,
            'total_cents' => (int) round((float) $log->hours * $log->rate),
            'worked_at'   => $log->worked_at?->toDateString(),
        ])->values()->all();

        $totalHours = round($logs->sum('hours'), 2);
        $totalCents = (int) $logs->sum(fn ($log) => round((float) $log->hours * $log->rate));
        $rateFormatted = $client->default_rate ? MoneyService::format($client->default_rate) : 'variable rate';

        $summary = "{$client->name}: {$totalHours} unbilled hours. " . MoneyService::format($totalCents) . " at {$rateFormatted}/hr.";

        return Response::text(json_encode([
            'data' => [
                'client_id'   => $client->id,
                'client_name' => $client->name,
                'entries'     => $data,
                'total_hours' => $totalHours,
                'total_cents' => $totalCents,
            ],
            'summary' => $summary,
        ]));
    }
}
