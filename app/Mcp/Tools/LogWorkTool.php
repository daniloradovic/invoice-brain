<?php

namespace App\Mcp\Tools;

use App\Enums\WorkLogStatus;
use App\Models\Client;
use App\Models\WorkLog;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class LogWorkTool extends Tool
{
    protected string $name = 'log_work';

    protected string $description = 'Logs a single work entry for a client (unbilled by default). Use when the user describes work done for a client. Rate defaults to the client\'s default_rate if not specified.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id'   => $schema->integer()->description('The ID of the client this work was done for')->required(),
            'description' => $schema->string()->description('Description of the work performed')->required(),
            'hours'       => $schema->number()->description('Number of hours worked')->required(),
            'worked_at'   => $schema->string()->description('Date work was performed (ISO 8601, e.g. 2025-01-15). Defaults to today.')->nullable(),
            'rate'        => $schema->integer()->description('Hourly rate in cents. Defaults to the client\'s default_rate.')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'client_id'   => 'required|integer|exists:clients,id',
            'description' => 'required|string',
            'hours'       => 'required|numeric|min:0.01',
            'worked_at'   => 'nullable|date',
            'rate'        => 'nullable|integer|min:1',
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $rate = $validated['rate'] ?? $client->default_rate;

        if (! $rate) {
            return Response::error("No rate specified and client '{$client->name}' has no default rate. Please provide a rate in cents.");
        }

        $workLog = WorkLog::create([
            'client_id'   => $client->id,
            'description' => $validated['description'],
            'hours'       => $validated['hours'],
            'rate'        => $rate,
            'worked_at'   => $validated['worked_at'] ?? now()->toDateString(),
            'status'      => WorkLogStatus::Unbilled->value,
        ]);

        $totalCents = (int) round($workLog->hours * $workLog->rate);
        $formatted = MoneyService::format($totalCents);

        return Response::text(
            "Logged {$workLog->hours}h for '{$client->name}': {$workLog->description}. " .
            "Total: {$formatted} at " . MoneyService::format($rate) . "/hr. Work log ID: {$workLog->id}."
        );
    }
}
