<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Enums\WorkLogStatus;
use App\Models\Client;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetClientReportTool extends Tool
{
    protected string $name = 'get_client_report';

    protected string $description = 'Returns a full performance report for a single client: lifetime revenue, average invoice size, average days to pay, outstanding balance, and unbilled work. Use to evaluate client relationship health.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->integer()->description('The ID of the client to report on')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $client = Client::with(['invoices.lineItems', 'workLogs'])->findOrFail($validated['client_id']);

        $allInvoices = $client->invoices;
        $paidInvoices = $allInvoices->filter(fn ($inv) => $inv->status === InvoiceStatus::Paid);
        $outstandingInvoices = $allInvoices->filter(
            fn ($inv) => in_array($inv->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue])
        );

        $lifetimeRevenue = $paidInvoices->sum(fn ($inv) => $inv->total);

        $avgInvoice = $allInvoices->isNotEmpty()
            ? (int) ($allInvoices->sum(fn ($inv) => $inv->total) / $allInvoices->count())
            : 0;

        $avgDaysToPay = 0;
        $payablePaid = $paidInvoices->filter(fn ($inv) => $inv->paid_at && $inv->issued_at);
        if ($payablePaid->isNotEmpty()) {
            $totalDays = $payablePaid->sum(
                fn ($inv) => $inv->issued_at->diffInDays($inv->paid_at)
            );
            $avgDaysToPay = (int) round($totalDays / $payablePaid->count());
        }

        $outstandingCents = $outstandingInvoices->sum(fn ($inv) => $inv->total);

        $unbilledLogs = $client->workLogs->filter(fn ($wl) => $wl->status === WorkLogStatus::Unbilled);
        $unbilledHours = (float) $unbilledLogs->sum('hours');
        $unbilledValue = $unbilledLogs->sum(fn ($wl) => $wl->hours * $wl->rate);

        $summaryParts = [
            "{$client->name}:",
            "Lifetime revenue " . MoneyService::format($lifetimeRevenue) . ".",
            "{$allInvoices->count()} invoice" . ($allInvoices->count() === 1 ? '' : 's') . ".",
        ];
        if ($outstandingCents > 0) {
            $summaryParts[] = MoneyService::format($outstandingCents) . " outstanding.";
        }
        if ($unbilledHours > 0) {
            $summaryParts[] = "{$unbilledHours}h unbilled (" . MoneyService::format((int) $unbilledValue) . ").";
        }
        if ($avgDaysToPay > 0) {
            $summaryParts[] = "Avg. days to pay: {$avgDaysToPay}.";
        }

        $data = [
            'client_id'               => $client->id,
            'client_name'             => $client->name,
            'lifetime_revenue_cents'  => $lifetimeRevenue,
            'lifetime_revenue'        => MoneyService::format($lifetimeRevenue),
            'avg_invoice_cents'       => $avgInvoice,
            'avg_invoice'             => MoneyService::format($avgInvoice),
            'avg_days_to_pay'         => $avgDaysToPay,
            'outstanding_cents'       => $outstandingCents,
            'outstanding'             => MoneyService::format($outstandingCents),
            'unbilled_hours'          => $unbilledHours,
            'unbilled_value_cents'    => (int) $unbilledValue,
            'unbilled_value'          => MoneyService::format((int) $unbilledValue),
            'invoice_count'           => $allInvoices->count(),
            'paid_count'              => $paidInvoices->count(),
            'summary_string'          => implode(' ', $summaryParts),
        ];

        return Response::text(json_encode($data));
    }
}
