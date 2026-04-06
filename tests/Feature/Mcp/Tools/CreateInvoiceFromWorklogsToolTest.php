<?php

namespace Tests\Feature\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Enums\WorkLogStatus;
use App\Mcp\Tools\CreateInvoiceFromWorklogsTool;
use App\Models\Client;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class CreateInvoiceFromWorklogsToolTest extends TestCase
{
    use RefreshDatabase;

    private CreateInvoiceFromWorklogsTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new CreateInvoiceFromWorklogsTool;
    }

    public function test_creates_invoice_from_unbilled_work_logs(): void
    {
        $client = Client::factory()->create(['payment_terms' => 30]);

        $logs = WorkLog::factory()
            ->count(3)
            ->unbilled()
            ->create([
                'client_id' => $client->id,
                'hours' => 2.00,
                'rate' => 10000,
            ]);

        $request = new Request([
            'client_id' => $client->id,
            'worklog_ids' => $logs->pluck('id')->all(),
        ]);

        $response = $this->tool->handle($request);

        $this->assertFalse($response->isError());
        $this->assertStringContains('created', (string) $response->content());

        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'status' => InvoiceStatus::Draft->value,
        ]);

        $this->assertDatabaseCount('invoice_line_items', 3);

        foreach ($logs as $log) {
            $this->assertDatabaseHas('work_logs', [
                'id' => $log->id,
                'status' => WorkLogStatus::Billed->value,
                'invoice_id' => 1,
            ]);
        }
    }

    public function test_fails_with_nonexistent_client_id(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $request = new Request([
            'client_id' => 9999,
            'worklog_ids' => [1],
        ]);

        $this->tool->handle($request);
    }

    public function test_fails_with_already_billed_work_log(): void
    {
        $client = Client::factory()->create();

        $unbilled = WorkLog::factory()->unbilled()->create(['client_id' => $client->id]);
        $billed = WorkLog::factory()->billed()->create(['client_id' => $client->id]);

        $request = new Request([
            'client_id' => $client->id,
            'worklog_ids' => [$unbilled->id, $billed->id],
        ]);

        $response = $this->tool->handle($request);

        $this->assertTrue($response->isError());
        $this->assertStringContains('already billed', (string) $response->content());
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_fails_with_empty_worklog_ids(): void
    {
        $client = Client::factory()->create();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $request = new Request([
            'client_id' => $client->id,
            'worklog_ids' => [],
        ]);

        $this->tool->handle($request);
    }

    public function test_fails_when_work_logs_belong_to_different_client(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $log = WorkLog::factory()->unbilled()->create(['client_id' => $clientB->id]);

        $request = new Request([
            'client_id' => $clientA->id,
            'worklog_ids' => [$log->id],
        ]);

        $response = $this->tool->handle($request);

        $this->assertTrue($response->isError());
        $this->assertStringContains('do not belong', (string) $response->content());
        $this->assertDatabaseCount('invoices', 0);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
