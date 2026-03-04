<?php

namespace App\Mcp\Resources;

use App\Models\WorkLog;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class WorkLogUnbilledResource extends Resource
{
    protected string $uri = 'worklogs://unbilled';

    protected string $description = 'All unbilled work logs grouped by client, with hours, rate, and total value';

    public function handle(Request $request): Response
    {
        $logs = WorkLog::with('client')->unbilled()->get();

        $grouped = $logs->groupBy('client_id')->map(function ($clientLogs) {
            $first = $clientLogs->first();

            return [
                'client_id'   => $first->client_id,
                'client_name' => $first->client->name,
                'entries'     => $clientLogs->map(fn ($log) => [
                    'id'          => $log->id,
                    'description' => $log->description,
                    'hours'       => (float) $log->hours,
                    'rate'        => $log->rate,
                    'total_cents' => (int) round((float) $log->hours * $log->rate),
                    'worked_at'   => $log->worked_at?->toDateString(),
                ])->values()->all(),
                'total_hours' => round($clientLogs->sum('hours'), 2),
                'total_cents' => (int) $clientLogs->sum(fn ($log) => round((float) $log->hours * $log->rate)),
            ];
        })->values()->all();

        $totalHours = round($logs->sum('hours'), 2);
        $totalCents = (int) $logs->sum(fn ($log) => round((float) $log->hours * $log->rate));
        $clientCount = count($grouped);

        $summary = "{$totalHours} unbilled hours across {$clientCount} clients. " . MoneyService::format($totalCents) . " total value.";

        return Response::text(json_encode([
            'data'    => $grouped,
            'summary' => $summary,
        ]));
    }
}
