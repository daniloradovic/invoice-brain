<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .wrapper { max-width: 640px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #1a1a2e; color: #fff; padding: 28px 32px; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 4px 0 0; font-size: 13px; color: #a0aec0; }
        .body { padding: 32px; }
        .body p { margin: 0 0 16px; line-height: 1.6; }
        .custom-message { background: #f7f8fc; border-left: 4px solid #4f46e5; padding: 12px 16px; margin: 0 0 24px; border-radius: 0 4px 4px 0; font-style: italic; color: #555; }
        table { width: 100%; border-collapse: collapse; margin: 24px 0; font-size: 14px; }
        thead th { background: #f7f8fc; text-align: left; padding: 10px 12px; font-weight: 600; color: #555; border-bottom: 2px solid #e2e8f0; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tbody tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .total-row td { font-weight: 700; font-size: 15px; border-top: 2px solid #1a1a2e; padding-top: 12px; }
        .meta { display: flex; gap: 32px; margin: 0 0 24px; }
        .meta-item { flex: 1; }
        .meta-item .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #888; margin-bottom: 4px; }
        .meta-item .value { font-size: 14px; font-weight: 600; color: #333; }
        .footer { background: #f7f8fc; padding: 20px 32px; font-size: 12px; color: #888; border-top: 1px solid #e2e8f0; }
        .footer a { color: #4f46e5; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Invoice Brain</h1>
        <p>Invoice {{ $invoice->invoice_number }}</p>
    </div>

    <div class="body">
        <p>Dear {{ $invoice->client->name }},</p>

        <p>Please find attached your invoice <strong>{{ $invoice->invoice_number }}</strong> for the services described below.</p>

        @if($customMessage)
            <div class="custom-message">{{ $customMessage }}</div>
        @endif

        <div class="meta">
            <div class="meta-item">
                <div class="label">Invoice Number</div>
                <div class="value">{{ $invoice->invoice_number }}</div>
            </div>
            <div class="meta-item">
                <div class="label">Issued</div>
                <div class="value">{{ $invoice->issued_at->format('d M Y') }}</div>
            </div>
            <div class="meta-item">
                <div class="label">Due Date</div>
                <div class="value">{{ $invoice->due_at->format('d M Y') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lineItems as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">@money($item->unit_price)</td>
                        <td class="text-right">@money($item->total)</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="text-right">Total</td>
                    <td class="text-right">@money($invoice->total)</td>
                </tr>
            </tbody>
        </table>

        @if($invoice->notes)
            <p><strong>Notes:</strong> {{ $invoice->notes }}</p>
        @endif

        <p>Please ensure payment is made by <strong>{{ $invoice->due_at->format('d M Y') }}</strong>. The invoice PDF is attached for your records.</p>

        <p>Thank you for your business.</p>

        <p>
            Kind regards,<br>
            <strong>Invoice Brain</strong>
        </p>
    </div>

    <div class="footer">
        This email was sent by Invoice Brain. If you have any questions regarding this invoice, please reply to this email.
    </div>
</div>
</body>
</html>
