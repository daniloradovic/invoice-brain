<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #1a1a2e; line-height: 1.5; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 24px; border-bottom: 2px solid #1a1a2e; }
        .brand { font-size: 22px; font-weight: 700; color: #1a1a2e; letter-spacing: -0.5px; }
        .brand-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .invoice-meta { text-align: right; }
        .invoice-number { font-size: 20px; font-weight: 700; color: #1a1a2e; }
        .invoice-meta p { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .addresses { display: flex; justify-content: space-between; margin-bottom: 36px; }
        .address-block h4 { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
        .address-block p { font-size: 13px; color: #374151; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead tr { background-color: #1a1a2e; }
        thead th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 600; color: #fff; text-transform: uppercase; letter-spacing: 0.06em; }
        thead th.right { text-align: right; }
        tbody tr { border-bottom: 1px solid #f3f4f6; }
        tbody tr:nth-child(even) { background-color: #f9fafb; }
        tbody td { padding: 10px 14px; font-size: 13px; color: #374151; }
        tbody td.right { text-align: right; }
        .total-row { border-top: 2px solid #1a1a2e; }
        .total-row td { padding: 12px 14px; font-weight: 700; font-size: 15px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; }
        .footer p { margin-bottom: 3px; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="brand">Invoice Brain</div>
            <div class="brand-sub">Billing &amp; Invoicing</div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <p>Issued: {{ $invoice->issued_at->format('F j, Y') }}</p>
            <p>Due: {{ $invoice->due_at->format('F j, Y') }}</p>
        </div>
    </div>

    {{-- Addresses --}}
    <div class="addresses">
        <div class="address-block">
            <h4>Bill To</h4>
            <p><strong>{{ $invoice->client->name }}</strong></p>
            <p>{{ $invoice->client->email }}</p>
            @if ($invoice->client->address)
                <p>{{ $invoice->client->address }}</p>
            @endif
        </div>
        <div class="address-block" style="text-align: right;">
            <h4>Payment Terms</h4>
            <p>Net {{ $invoice->client->payment_terms }} days</p>
            <p style="margin-top:4px; font-size:11px; color:#6b7280;">
                Please include the invoice number<br>with your payment.
            </p>
        </div>
    </div>

    {{-- Line Items --}}
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right" style="width:80px;">Qty</th>
                <th class="right" style="width:110px;">Unit Price</th>
                <th class="right" style="width:110px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lineItems as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ \App\Services\MoneyService::format($item->unit_price) }}</td>
                    <td class="right">{{ \App\Services\MoneyService::format($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align:right; padding-right:14px;">Total Due</td>
                <td class="right">{{ \App\Services\MoneyService::format($invoice->total) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Notes --}}
    @if ($invoice->notes)
        <div style="margin-top: 20px; padding: 14px; background: #f9fafb; border-radius: 6px; font-size: 12px; color: #6b7280;">
            <strong style="color: #374151;">Notes:</strong> {{ $invoice->notes }}
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>Thank you for your business. Payment is due within {{ $invoice->client->payment_terms }} days of the invoice date.</p>
        <p>Questions? Reply to the email this invoice was sent from.</p>
    </div>

</body>
</html>
