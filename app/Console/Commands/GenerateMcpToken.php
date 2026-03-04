<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateMcpToken extends Command
{
    protected $signature = 'mcp:token';

    protected $description = 'Generate a Sanctum token for MCP access';

    public function handle(): int
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@invoice-brain.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make(Str::random(32)),
            ]
        );

        $user->tokens()->delete();

        $token = $user->createToken('mcp-demo')->plainTextToken;

        $this->line('');
        $this->info("Your MCP token: {$token}");
        $this->line('Add this as a Bearer token in your MCP client config.');
        $this->line('');

        return self::SUCCESS;
    }
}
