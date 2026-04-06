<?php

namespace Tests\Feature\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Enums\WorkLogStatus;
use App\Mcp\Tools\CancelInvoiceTool;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class CancelInvoiceToolTest extends TestCase
{
    use RefreshDatabase;

    private CancelInvoiceTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new CancelInvoiceTool;
    }

    public function test_cancelling_sent_invoice_unbills_work_logs(): void
    {
        $client = Client::factory()->create();

        $invoice = Invoice::factory()->sent()->create([
            'client_id' => $client->id,
        ]);

        InvoiceLineItem::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $workLogs = WorkLog::factory()->count(3)->create([
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'status' => WorkLogStatus::Billed->value,
        ]);

        $request = new Request([
            'invoice_id' => $invoice->id,
            'reason' => 'Client requested changes',
        ]);

        $response = $this->tool->handle($request);

        $this->assertFalse($response->isError());
        $this->assertStringContainsString('cancelled', (string) $response->content());
        $this->assertStringContainsString('3 work logs returned to unbilled', (string) $response->content());

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Cancelled->value,
        ]);

        foreach ($workLogs as $workLog) {
            $this->assertDatabaseHas('work_logs', [
                'id' => $workLog->id,
                'status' => WorkLogStatus::Unbilled->value,
                'invoice_id' => null,
            ]);
        }
    }

    public function test_cannot_cancel_paid_invoice(): void
    {
        $client = Client::factory()->create();

        $invoice = Invoice::factory()->paid()->create([
            'client_id' => $client->id,
        ]);

        $request = new Request([
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->tool->handle($request);

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('already been paid', (string) $response->content());

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
        ]);
    }

    public function test_cancellation_reason_appended_to_notes(): void
    {
        $client = Client::factory()->create();

        $invoice = Invoice::factory()->sent()->create([
            'client_id' => $client->id,
            'notes' => 'Original notes',
        ]);

        $request = new Request([
            'invoice_id' => $invoice->id,
            'reason' => 'Duplicate invoice',
        ]);

        $this->tool->handle($request);

        $invoice->refresh();

        $this->assertStringContainsString('[CANCELLED', $invoice->notes);
        $this->assertStringContainsString('Duplicate invoice', $invoice->notes);
        $this->assertStringContainsString('Original notes', $invoice->notes);
    }

    public function test_cancelling_draft_invoice_with_no_work_logs(): void
    {
        $client = Client::factory()->create();

        $invoice = Invoice::factory()->draft()->create([
            'client_id' => $client->id,
        ]);

        $request = new Request([
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->tool->handle($request);

        $this->assertFalse($response->isError());
        $this->assertStringContainsString('cancelled', (string) $response->content());
        $this->assertStringNotContainsString('work log', (string) $response->content());

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Cancelled->value,
        ]);
    }
}
