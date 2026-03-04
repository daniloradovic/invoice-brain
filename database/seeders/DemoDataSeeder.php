<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\WorkLogStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\WorkLog;
use App\Services\InvoiceNumberService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function __construct(private readonly InvoiceNumberService $invoiceNumbers) {}

    public function run(): void
    {
        $acme = Client::create([
            'name'          => 'Acme Corp',
            'email'         => 'billing@acme.com',
            'default_rate'  => 12000,
            'payment_terms' => 30,
            'notes'         => 'Large enterprise client. Tends to pay 2-3 weeks late. Always pays eventually.',
        ]);

        $bright = Client::create([
            'name'          => 'Bright Studio',
            'email'         => 'hello@brightstudio.io',
            'default_rate'  => 9500,
            'payment_terms' => 14,
            'notes'         => 'Design agency. Fast payer, clear briefs.',
        ]);

        $nova = Client::create([
            'name'          => 'Nova Health',
            'email'         => 'accounts@novahealth.com',
            'default_rate'  => 15000,
            'payment_terms' => 30,
            'notes'         => 'Healthcare startup. Requires formal invoices with detailed line items.',
        ]);

        $techstart = Client::create([
            'name'          => 'TechStart Ltd',
            'email'         => 'finance@techstart.dev',
            'default_rate'  => 11000,
            'payment_terms' => 21,
        ]);

        // Acme Corp — 2 overdue sent invoices
        $acmeInvoice1 = Invoice::create([
            'client_id'      => $acme->id,
            'invoice_number' => $this->invoiceNumbers->generate(),
            'status'         => InvoiceStatus::Sent->value,
            'issued_at'      => now()->subDays(60),
            'due_at'         => now()->subDays(45),
        ]);

        InvoiceLineItem::insert([
            [
                'invoice_id'  => $acmeInvoice1->id,
                'description' => 'API Development Services',
                'quantity'    => 10.00,
                'unit_price'  => 12000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'invoice_id'  => $acmeInvoice1->id,
                'description' => 'Technical Consulting',
                'quantity'    => 15.00,
                'unit_price'  => 12000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        $acmeInvoice2 = Invoice::create([
            'client_id'      => $acme->id,
            'invoice_number' => $this->invoiceNumbers->generate(),
            'status'         => InvoiceStatus::Sent->value,
            'issued_at'      => now()->subDays(35),
            'due_at'         => now()->subDays(20),
        ]);

        InvoiceLineItem::insert([
            [
                'invoice_id'  => $acmeInvoice2->id,
                'description' => 'Backend Infrastructure Setup',
                'quantity'    => 12.00,
                'unit_price'  => 12000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'invoice_id'  => $acmeInvoice2->id,
                'description' => 'Code Review & Optimisation',
                'quantity'    => 8.00,
                'unit_price'  => 12000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'invoice_id'  => $acmeInvoice2->id,
                'description' => 'DevOps Configuration',
                'quantity'    => 6.00,
                'unit_price'  => 12000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // Bright Studio — 1 paid invoice
        $brightInvoice = Invoice::create([
            'client_id'      => $bright->id,
            'invoice_number' => $this->invoiceNumbers->generate(),
            'status'         => InvoiceStatus::Paid->value,
            'issued_at'      => now()->subMonths(2),
            'due_at'         => now()->subMonths(2)->addDays(14),
            'paid_at'        => now()->subMonth(),
        ]);

        InvoiceLineItem::insert([
            [
                'invoice_id'  => $brightInvoice->id,
                'description' => 'Website Redesign Phase 1',
                'quantity'    => 20.00,
                'unit_price'  => 9500,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'invoice_id'  => $brightInvoice->id,
                'description' => 'Brand Guidelines Implementation',
                'quantity'    => 10.00,
                'unit_price'  => 9500,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // Bright Studio — 3 unbilled work logs
        WorkLog::insert([
            [
                'client_id'   => $bright->id,
                'invoice_id'  => null,
                'description' => 'React component library setup',
                'hours'       => 4.00,
                'rate'        => 9500,
                'worked_at'   => now()->subDay()->toDateString(),
                'status'      => WorkLogStatus::Unbilled->value,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'client_id'   => $bright->id,
                'invoice_id'  => null,
                'description' => 'Design system documentation',
                'hours'       => 2.50,
                'rate'        => 9500,
                'worked_at'   => now()->subDays(2)->toDateString(),
                'status'      => WorkLogStatus::Unbilled->value,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'client_id'   => $bright->id,
                'invoice_id'  => null,
                'description' => 'Figma to code handoff review',
                'hours'       => 3.00,
                'rate'        => 9500,
                'worked_at'   => now()->subDays(3)->toDateString(),
                'status'      => WorkLogStatus::Unbilled->value,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // Nova Health — 1 draft invoice
        $novaInvoice = Invoice::create([
            'client_id'      => $nova->id,
            'invoice_number' => $this->invoiceNumbers->generate(),
            'status'         => InvoiceStatus::Draft->value,
            'issued_at'      => now(),
            'due_at'         => now()->addDays(30),
        ]);

        InvoiceLineItem::insert([
            [
                'invoice_id'  => $novaInvoice->id,
                'description' => 'Healthcare Platform Consulting — Day 1',
                'quantity'    => 1.00,
                'unit_price'  => 80000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'invoice_id'  => $novaInvoice->id,
                'description' => 'Healthcare Platform Consulting — Day 2',
                'quantity'    => 1.00,
                'unit_price'  => 80000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'invoice_id'  => $novaInvoice->id,
                'description' => 'Healthcare Platform Consulting — Day 3',
                'quantity'    => 1.00,
                'unit_price'  => 80000,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // TechStart Ltd — 2 unbilled work logs, no invoices
        WorkLog::insert([
            [
                'client_id'   => $techstart->id,
                'invoice_id'  => null,
                'description' => 'API integration planning session',
                'hours'       => 2.00,
                'rate'        => 11000,
                'worked_at'   => now()->subDay()->toDateString(),
                'status'      => WorkLogStatus::Unbilled->value,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'client_id'   => $techstart->id,
                'invoice_id'  => null,
                'description' => 'Authentication flow implementation',
                'hours'       => 5.00,
                'rate'        => 11000,
                'worked_at'   => now()->toDateString(),
                'status'      => WorkLogStatus::Unbilled->value,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
