<?php

namespace App\Mcp\Tools;

use App\Models\Client;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateClientNotesTool extends Tool
{
    protected string $name = 'update_client_notes';

    protected string $description = 'Updates the notes field for a client. Use when the user shares context about a client that should be remembered (payment behaviour, preferences, special instructions). Appends to existing notes with a timestamp.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->integer()->description('The ID of the client to update')->required(),
            'notes'     => $schema->string()->description('New notes to append to the client record')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'notes'     => 'required|string',
        ]);

        $client = Client::findOrFail($validated['client_id']);

        $date = now()->toDateString();
        $newNotes = "[{$date}] {$validated['notes']}";

        if ($client->notes) {
            $newNotes = $newNotes . "\n" . $client->notes;
        }

        $client->update(['notes' => $newNotes]);

        return Response::text("Notes updated for client '{$client->name}'.\n\nCurrent notes:\n{$client->notes}");
    }
}
