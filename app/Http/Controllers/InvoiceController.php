<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $query = Invoice::with(['client', 'lineItems'])->orderByDesc('issued_at');

        if ($status && InvoiceStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $invoices = $query->get();

        return view('invoices.index', compact('invoices', 'status'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['client', 'lineItems']);

        return view('invoices.show', compact('invoice'));
    }
}
