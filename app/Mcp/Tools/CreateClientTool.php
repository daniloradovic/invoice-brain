<?php

namespace App\Mcp\Tools;

use App\Models\Client;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateClientTool extends Tool
{
    protected string $name = 'create_client';

    protected string $description = 'Creates a new client. Use when the user mentions a new client that doesn\'t exist yet. Check clients://list first to avoid duplicates.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'name'          => $schema->string()->description('Client company name')->required(),
            'email'         => $schema->string()->description('Billing email address')->required(),
            'address'       => $schema->string()->description('Postal address')->nullable(),
            'default_rate'  => $schema->integer()->description('Hourly rate in cents')->nullable(),
            'payment_terms' => $schema->integer()->description('Days until invoice is due')->nullable(),
            'notes'         => $schema->string()->description('Internal notes about the client')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name'          => 'required|string',
            'email'         => 'required|email|unique:clients,email',
            'address'       => 'nullable|string',
            'default_rate'  => 'nullable|integer|min:1',
            'payment_terms' => 'nullable|integer|min:1',
            'notes'         => 'nullable|string',
        ]);

        $client = Client::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'address'       => $validated['address'] ?? null,
            'default_rate'  => $validated['default_rate'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? 30,
            'notes'         => $validated['notes'] ?? null,
        ]);

        return Response::text("Client '{$client->name}' created with ID {$client->id}.");
    }
}
