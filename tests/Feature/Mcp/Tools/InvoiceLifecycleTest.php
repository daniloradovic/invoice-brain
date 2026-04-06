<?php

namespace Tests\Feature\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Mcp\Tools\CreateInvoiceTool;
use App\Mcp\Tools\MarkInvoicePaidTool;
use App\Mcp\Tools\SendInvoiceTool;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Mcp\Request;
use Tests\TestCase;

class InvoiceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_invoice_lifecycle_create_send_and_mark_paid(): void
    {
        Mail::fake();

        $client = Client::factory()->create([
            'payment_terms' => 30,
            'email' => 'billing@lifecycle-test.com',
        ]);

        $createTool = new CreateInvoiceTool;
        $createResponse = $createTool->handle(new Request([
            'client_id' => $client->id,
            'line_items' => [
                ['description' => 'Web development', 'quantity' => 10, 'unit_price' => 15000],
                ['description' => 'Code review', 'quantity' => 3, 'unit_price' => 12000],
            ],
            'notes' => 'Net 30 terms',
        ]));

        $this->assertFalse($createResponse->isError());
        $this->assertStringContainsString('draft', (string) $createResponse->content());

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals(InvoiceStatus::Draft, $invoice->status);
        $this->assertEquals($client->id, $invoice->client_id);
        $this->assertDatabaseCount('invoice_line_items', 2);

        $sendTool = new SendInvoiceTool;
        $sendResponse = $sendTool->handle(new Request([
            'invoice_id' => $invoice->id,
        ]));

        $this->assertFalse($sendResponse->isError());
        $this->assertStringContainsString('sent', (string) $sendResponse->content());

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::Sent, $invoice->status);

        $paidDate = '2026-04-10';
        $paidTool = new MarkInvoicePaidTool;
        $paidResponse = $paidTool->handle(new Request([
            'invoice_id' => $invoice->id,
            'paid_at' => $paidDate,
        ]));

        $this->assertFalse($paidResponse->isError());
        $this->assertStringContainsString('marked as paid', (string) $paidResponse->content());

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::Paid, $invoice->status);
        $this->assertEquals($paidDate, $invoice->paid_at->toDateString());
    }

    public function test_cannot_send_non_draft_invoice(): void
    {
        Mail::fake();

        $client = Client::factory()->create();
        $invoice = Invoice::factory()->sent()->create(['client_id' => $client->id]);

        $sendTool = new SendInvoiceTool;
        $response = $sendTool->handle(new Request([
            'invoice_id' => $invoice->id,
        ]));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('cannot be sent', (string) $response->content());
    }

    public function test_cannot_mark_draft_invoice_as_paid(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->draft()->create(['client_id' => $client->id]);

        $paidTool = new MarkInvoicePaidTool;
        $response = $paidTool->handle(new Request([
            'invoice_id' => $invoice->id,
        ]));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('cannot be marked paid', (string) $response->content());
    }

    public function test_paid_at_defaults_to_today_if_not_provided(): void
    {
        Mail::fake();

        $client = Client::factory()->create();
        $invoice = Invoice::factory()->sent()->create(['client_id' => $client->id]);

        $paidTool = new MarkInvoicePaidTool;
        $paidTool->handle(new Request([
            'invoice_id' => $invoice->id,
        ]));

        $invoice->refresh();
        $this->assertEquals(now()->toDateString(), $invoice->paid_at->toDateString());
    }
}
