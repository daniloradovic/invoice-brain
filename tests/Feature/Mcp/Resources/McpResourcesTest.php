<?php

namespace Tests\Feature\Mcp\Resources;

use App\Enums\InvoiceStatus;
use App\Mcp\Resources\ClientListResource;
use App\Mcp\Resources\InvoiceOverdueResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class McpResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_list_resource_returns_correct_structure(): void
    {
        $client = Client::factory()->create(['name' => 'Test Corp']);
        WorkLog::factory()->unbilled()->create(['client_id' => $client->id, 'hours' => 5]);

        $resource = new ClientListResource;
        $response = $resource->handle(new Request);

        $this->assertFalse($response->isError());

        $json = json_decode((string) $response->content(), true);

        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('summary', $json);
        $this->assertIsString($json['summary']);

        $this->assertCount(1, $json['data']);

        $item = $json['data'][0];
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('email', $item);
        $this->assertArrayHasKey('default_rate_formatted', $item);
        $this->assertArrayHasKey('payment_terms', $item);
        $this->assertArrayHasKey('open_invoices_count', $item);
        $this->assertArrayHasKey('overdue_invoices_count', $item);
        $this->assertArrayHasKey('unbilled_hours', $item);
        $this->assertArrayHasKey('notes', $item);

        $this->assertEquals('Test Corp', $item['name']);
        $this->assertEquals(5.0, $item['unbilled_hours']);
    }

    public function test_client_list_resource_with_no_clients(): void
    {
        $resource = new ClientListResource;
        $response = $resource->handle(new Request);

        $json = json_decode((string) $response->content(), true);

        $this->assertArrayHasKey('data', $json);
        $this->assertEmpty($json['data']);
        $this->assertStringContainsString('0 clients', $json['summary']);
    }

    public function test_invoice_overdue_resource_returns_correct_structure(): void
    {
        $client = Client::factory()->create(['name' => 'Late Payer Inc']);

        $overdue = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => InvoiceStatus::Sent->value,
            'due_at' => now()->subDays(15),
            'issued_at' => now()->subDays(45),
        ]);
        InvoiceLineItem::factory()->create([
            'invoice_id' => $overdue->id,
            'quantity' => 1,
            'unit_price' => 50000,
        ]);

        $resource = new InvoiceOverdueResource;
        $response = $resource->handle(new Request);

        $this->assertFalse($response->isError());

        $json = json_decode((string) $response->content(), true);

        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('summary', $json);
        $this->assertIsString($json['summary']);

        $this->assertCount(1, $json['data']);

        $item = $json['data'][0];
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('invoice_number', $item);
        $this->assertArrayHasKey('client_name', $item);
        $this->assertArrayHasKey('total_cents', $item);
        $this->assertArrayHasKey('status', $item);
        $this->assertArrayHasKey('due_at', $item);
        $this->assertArrayHasKey('days_overdue', $item);

        $this->assertEquals('Late Payer Inc', $item['client_name']);
        $this->assertGreaterThanOrEqual(15, $item['days_overdue']);
    }

    public function test_invoice_overdue_resource_excludes_paid_invoices(): void
    {
        $client = Client::factory()->create();

        Invoice::factory()->paid()->create([
            'client_id' => $client->id,
            'due_at' => now()->subDays(30),
        ]);

        $resource = new InvoiceOverdueResource;
        $response = $resource->handle(new Request);

        $json = json_decode((string) $response->content(), true);

        $this->assertEmpty($json['data']);
        $this->assertStringContainsString('0 overdue', $json['summary']);
    }

    public function test_invoice_overdue_resource_summary_includes_totals(): void
    {
        $client = Client::factory()->create();

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => InvoiceStatus::Overdue->value,
            'due_at' => now()->subDays(20),
            'issued_at' => now()->subDays(50),
        ]);
        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 2,
            'unit_price' => 25000,
        ]);

        $resource = new InvoiceOverdueResource;
        $response = $resource->handle(new Request);

        $json = json_decode((string) $response->content(), true);

        $this->assertStringContainsString('1 overdue', $json['summary']);
        $this->assertStringContainsString('$', $json['summary']);
    }
}
