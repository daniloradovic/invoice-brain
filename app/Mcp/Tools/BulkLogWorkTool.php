<?php

namespace App\Mcp\Tools;

use App\Enums\WorkLogStatus;
use App\Models\Client;
use App\Models\WorkLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class BulkLogWorkTool extends Tool
{
    protected string $name = 'bulk_log_work';

    protected string $description = 'Logs multiple work entries in one call. Use when the user provides a list or summary of work across multiple days or tasks. More efficient than calling log_work repeatedly.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'entries' => $schema->array()
                ->description('Array of work log entries to create')
                ->items($schema->object([
                    'client_id'   => $schema->integer()->description('Client ID')->required(),
                    'description' => $schema->string()->description('Description of the work performed')->required(),
                    'hours'       => $schema->number()->description('Number of hours worked')->required(),
                    'worked_at'   => $schema->string()->description('Date of work (ISO 8601). Defaults to today.')->nullable(),
                    'rate'        => $schema->integer()->description('Hourly rate in cents. Defaults to the client\'s default_rate.')->nullable(),
                ]))
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entries'               => 'required|array|min:1',
            'entries.*.client_id'   => 'required|integer|exists:clients,id',
            'entries.*.description' => 'required|string',
            'entries.*.hours'       => 'required|numeric|min:0.01',
            'entries.*.worked_at'   => 'nullable|date',
            'entries.*.rate'        => 'nullable|integer|min:1',
        ]);

        $clientCache = [];
        $errors = [];
        $created = [];

        foreach ($validated['entries'] as $index => $entry) {
            $clientId = $entry['client_id'];

            if (! isset($clientCache[$clientId])) {
                $clientCache[$clientId] = Client::find($clientId);
            }

            $client = $clientCache[$clientId];
            $rate = $entry['rate'] ?? $client->default_rate;

            if (! $rate) {
                $errors[] = "Entry {$index}: no rate specified and client '{$client->name}' has no default rate.";
                continue;
            }

            $created[] = WorkLog::create([
                'client_id'   => $clientId,
                'description' => $entry['description'],
                'hours'       => $entry['hours'],
                'rate'        => $rate,
                'worked_at'   => $entry['worked_at'] ?? now()->toDateString(),
                'status'      => WorkLogStatus::Unbilled->value,
            ]);
        }

        $count = count($created);
        $totalHours = collect($created)->sum('hours');
        $summary = "Logged {$count} " . ($count === 1 ? 'entry' : 'entries') . " totalling {$totalHours} hours.";

        if ($errors) {
            $summary .= ' Errors: ' . implode(' ', $errors);
        }

        return Response::text($summary);
    }
}
