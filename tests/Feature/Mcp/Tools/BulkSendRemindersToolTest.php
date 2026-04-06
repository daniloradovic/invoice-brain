<?php

namespace Tests\Feature\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Mcp\Tools\BulkSendRemindersTool;
use App\Mail\PaymentReminderMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Mcp\Request;
use Tests\TestCase;

class BulkSendRemindersToolTest extends TestCase
{
    use RefreshDatabase;

    private BulkSendRemindersTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new BulkSendRemindersTool;
    }

    public function test_sends_reminders_to_overdue_invoices(): void
    {
        Mail::fake();

        $client = Client::factory()->create(['email' => 'billing@test.com']);

        $overdue = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => InvoiceStatus::Overdue->value,
            'due_at' => now()->subDays(10),
            'issued_at' => now()->subDays(40),
        ]);
        InvoiceLineItem::factory()->create(['invoice_id' => $overdue->id]);

        $request = new Request([
            'days_overdue_min' => 5,
        ]);

        $response = $this->tool->handle($request);

        $this->assertFalse($response->isError());
        $this->assertStringContainsString('Sent 1 reminder', (string) $response->content());

        Mail::assertSent(PaymentReminderMail::class, 1);

        $overdue->refresh();
        $this->assertStringContainsString('[REMINDER SENT', $overdue->notes);
    }

    public function test_skips_recently_reminded_invoices(): void
    {
        Mail::fake();

        $client = Client::factory()->create();
        $recentDate = now()->subHours(24)->toDateString();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => InvoiceStatus::Overdue->value,
            'due_at' => now()->subDays(10),
            'issued_at' => now()->subDays(40),
            'notes' => "[REMINDER SENT {$recentDate}]",
        ]);

        $request = new Request([]);

        $response = $this->tool->handle($request);

        $this->assertFalse($response->isError());
        $this->assertStringContainsString('Sent 0 reminders', (string) $response->content());
        $this->assertStringContainsString('Skipped 1', (string) $response->content());

        Mail::assertNothingSent();
    }

    public function test_only_sends_to_invoices_past_threshold(): void
    {
        Mail::fake();

        $client = Client::factory()->create();

        $barelyOverdue = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => InvoiceStatus::Overdue->value,
            'due_at' => now()->subDays(2),
            'issued_at' => now()->subDays(32),
        ]);

        $veryOverdue = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => InvoiceStatus::Overdue->value,
            'due_at' => now()->subDays(15),
            'issued_at' => now()->subDays(45),
        ]);
        InvoiceLineItem::factory()->create(['invoice_id' => $veryOverdue->id]);

        $request = new Request([
            'days_overdue_min' => 10,
        ]);

        $response = $this->tool->handle($request);

        $this->assertStringContainsString('Sent 1 reminder', (string) $response->content());

        Mail::assertSent(PaymentReminderMail::class, 1);
    }

    public function test_does_not_send_to_non_overdue_invoices(): void
    {
        Mail::fake();

        $client = Client::factory()->create();

        Invoice::factory()->draft()->create(['client_id' => $client->id]);
        Invoice::factory()->paid()->create(['client_id' => $client->id]);

        $request = new Request([]);

        $response = $this->tool->handle($request);

        $this->assertStringContainsString('Sent 0 reminders', (string) $response->content());
        Mail::assertNothingSent();
    }
}
