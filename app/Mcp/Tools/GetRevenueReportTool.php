<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Services\MoneyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRevenueReportTool extends Tool
{
    protected string $name = 'get_revenue_report';

    protected string $description = 'Returns revenue statistics for a given period. Use when the user asks about earnings, income, or financial performance.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'period'     => $schema->string()
                ->description('Predefined period: this_month, last_month, this_year, last_year')
                ->enum(['this_month', 'last_month', 'this_year', 'last_year'])
                ->required(),
            'start_date' => $schema->string()->description('Custom start date (ISO 8601). Overrides period if provided.')->nullable(),
            'end_date'   => $schema->string()->description('Custom end date (ISO 8601). Overrides period if provided.')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'period'     => 'required|string|in:this_month,last_month,this_year,last_year',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        [$start, $end] = $this->resolveDateRange($validated);

        $invoices = Invoice::with('lineItems')
            ->whereBetween('issued_at', [$start, $end])
            ->get();

        $invoiced = $invoices->sum(fn (Invoice $inv) => $inv->total);

        $paidInvoices = $invoices->filter(fn (Invoice $inv) => $inv->status === InvoiceStatus::Paid);
        $collected = $paidInvoices->sum(fn (Invoice $inv) => $inv->total);

        $outstandingInvoices = $invoices->filter(
            fn (Invoice $inv) => in_array($inv->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue])
        );
        $outstanding = $outstandingInvoices->sum(fn (Invoice $inv) => $inv->total);

        $topClient = $paidInvoices->groupBy('client_id')
            ->map(fn ($group) => $group->sum(fn (Invoice $inv) => $inv->total))
            ->sortDesc()
            ->keys()
            ->first();

        $topClientName = null;
        if ($topClient) {
            $topClientName = $paidInvoices->firstWhere('client_id', $topClient)?->client?->name
                ?? Invoice::with('client')->find($topClient)?->client?->name;
        }

        $data = [
            'period'           => $validated['period'],
            'start_date'       => $start->toDateString(),
            'end_date'         => $end->toDateString(),
            'invoiced_cents'   => $invoiced,
            'invoiced'         => MoneyService::format($invoiced),
            'collected_cents'  => $collected,
            'collected'        => MoneyService::format($collected),
            'outstanding_cents'=> $outstanding,
            'outstanding'      => MoneyService::format($outstanding),
            'invoice_count'    => $invoices->count(),
            'paid_count'       => $paidInvoices->count(),
            'top_client'       => $topClientName,
        ];

        return Response::text(json_encode($data));
    }

    private function resolveDateRange(array $validated): array
    {
        if (($validated['start_date'] ?? null) && ($validated['end_date'] ?? null)) {
            return [
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay(),
            ];
        }

        return match ($validated['period']) {
            'this_month'  => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month'  => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_year'   => [now()->startOfYear(), now()->endOfYear()],
            'last_year'   => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
        };
    }
}
