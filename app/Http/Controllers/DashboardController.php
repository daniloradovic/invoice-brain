<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalInvoiced = Invoice::whereYear('issued_at', now()->year)
            ->with('lineItems')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->total);

        $totalPaid = Invoice::whereYear('issued_at', now()->year)
            ->where('status', InvoiceStatus::Paid->value)
            ->with('lineItems')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->total);

        $outstanding = Invoice::outstanding()
            ->with('lineItems')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->total);

        $overdueCount = Invoice::overdue()->count();

        $overdueInvoices = Invoice::overdue()
            ->with(['client', 'lineItems'])
            ->orderBy('due_at')
            ->get();

        $draftInvoices = Invoice::draft()
            ->with(['client', 'lineItems'])
            ->orderBy('created_at')
            ->get();

        $recentPaid = Invoice::where('status', InvoiceStatus::Paid->value)
            ->with(['client', 'lineItems'])
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalInvoiced',
            'totalPaid',
            'outstanding',
            'overdueCount',
            'overdueInvoices',
            'draftInvoices',
            'recentPaid',
        ));
    }
}
