<?php

namespace App\Mcp\Resources;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\WorkLog;
use App\Services\MoneyService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ReportSummaryResource extends Resource
{
    protected string $uri = 'reports://summary';

    protected string $description = 'Financial summary report including YTD invoiced, collected, outstanding, and overdue amounts';

    public function handle(Request $request): Response
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        $allInvoices = Invoice::with('lineItems')->get();

        $invoicedThisMonth = $allInvoices
            ->filter(fn ($i) => $i->issued_at && $i->issued_at->greaterThanOrEqualTo($startOfMonth))
            ->sum('total');

        $invoicedYtd = $allInvoices
            ->filter(fn ($i) => $i->issued_at && $i->issued_at->greaterThanOrEqualTo($startOfYear))
            ->sum('total');

        $collectedThisMonth = $allInvoices
            ->filter(fn ($i) => $i->status === InvoiceStatus::Paid && $i->paid_at && $i->paid_at->greaterThanOrEqualTo($startOfMonth))
            ->sum('total');

        $collectedYtd = $allInvoices
            ->filter(fn ($i) => $i->status === InvoiceStatus::Paid && $i->paid_at && $i->paid_at->greaterThanOrEqualTo($startOfYear))
            ->sum('total');

        $outstanding = $allInvoices
            ->filter(fn ($i) => in_array($i->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue]))
            ->sum('total');

        $overdue = $allInvoices
            ->filter(fn ($i) => $i->status === InvoiceStatus::Overdue || ($i->status === InvoiceStatus::Sent && $i->due_at->isPast()))
            ->sum('total');

        $invoicedThisMonthCents   = (int) $invoicedThisMonth;
        $invoicedYtdCents         = (int) $invoicedYtd;
        $collectedThisMonthCents  = (int) $collectedThisMonth;
        $collectedYtdCents        = (int) $collectedYtd;
        $outstandingCents         = (int) $outstanding;
        $overdueCents             = (int) $overdue;

        $clientCount    = Client::count();
        $invoiceCountYtd = $allInvoices
            ->filter(fn ($i) => $i->issued_at && $i->issued_at->greaterThanOrEqualTo($startOfYear))
            ->count();

        $summary = "YTD: " . MoneyService::format($invoicedYtdCents) . " invoiced, " . MoneyService::format($collectedYtdCents) . " collected. " . MoneyService::format($outstandingCents) . " outstanding (" . MoneyService::format($overdueCents) . " overdue).";

        return Response::text(json_encode([
            'data' => [
                'invoiced_this_month_cents'  => $invoicedThisMonthCents,
                'invoiced_ytd_cents'         => $invoicedYtdCents,
                'collected_this_month_cents' => $collectedThisMonthCents,
                'collected_ytd_cents'        => $collectedYtdCents,
                'outstanding_cents'          => $outstandingCents,
                'overdue_cents'              => $overdueCents,
                'client_count'               => $clientCount,
                'invoice_count_ytd'          => $invoiceCountYtd,
            ],
            'summary' => $summary,
        ]));
    }
}
