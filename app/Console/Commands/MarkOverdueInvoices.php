<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark all sent invoices past their due date as overdue';

    public function handle(): int
    {
        $count = Invoice::where('status', InvoiceStatus::Sent->value)
            ->where('due_at', '<', now()->startOfDay())
            ->update(['status' => InvoiceStatus::Overdue->value]);

        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
