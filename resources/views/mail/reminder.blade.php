<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminder: {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .wrapper { max-width: 640px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #1a1a2e; color: #fff; padding: 28px 32px; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 4px 0 0; font-size: 13px; color: #a0aec0; }
        .body { padding: 32px; }
        .body p { margin: 0 0 16px; line-height: 1.6; }
        .custom-message { background: #f7f8fc; border-left: 4px solid #4f46e5; padding: 12px 16px; margin: 0 0 24px; border-radius: 0 4px 4px 0; font-style: italic; color: #555; }
        .summary-box { background: #fff8f0; border: 1px solid #fed7aa; border-radius: 6px; padding: 20px 24px; margin: 24px 0; }
        .summary-box .row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #fde8cc; font-size: 14px; }
        .summary-box .row:last-child { border-bottom: none; font-weight: 700; font-size: 15px; }
        .summary-box .label { color: #666; }
        .summary-box .value { font-weight: 600; color: #333; }
        .overdue-badge { display: inline-block; background: #fee2e2; color: #b91c1c; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 8px; }
        .footer { background: #f7f8fc; padding: 20px 32px; font-size: 12px; color: #888; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Invoice Brain</h1>
        <p>Payment Reminder</p>
    </div>

    <div class="body">
        <p>Dear {{ $invoice->client->name }},</p>

        <p>
            This is a friendly reminder that invoice <strong>{{ $invoice->invoice_number }}</strong>
            is currently outstanding.
            @if($invoice->due_at->isPast())
                <span class="overdue-badge">Overdue by {{ $invoice->due_at->diffForHumans(null, true) }}</span>
            @endif
        </p>

        @if($customMessage)
            <div class="custom-message">{{ $customMessage }}</div>
        @endif

        <div class="summary-box">
            <div class="row">
                <span class="label">Invoice Number</span>
                <span class="value">{{ $invoice->invoice_number }}</span>
            </div>
            <div class="row">
                <span class="label">Issued</span>
                <span class="value">{{ $invoice->issued_at->format('d M Y') }}</span>
            </div>
            <div class="row">
                <span class="label">Due Date</span>
                <span class="value">{{ $invoice->due_at->format('d M Y') }}</span>
            </div>
            <div class="row">
                <span class="label">Amount Due</span>
                <span class="value">@money($invoice->total)</span>
            </div>
        </div>

        <p>If you have already made payment, please disregard this reminder and accept our thanks.</p>

        <p>If you have any questions about this invoice, please don't hesitate to reply to this email.</p>

        <p>
            Kind regards,<br>
            <strong>Invoice Brain</strong>
        </p>
    </div>

    <div class="footer">
        This is an automated payment reminder from Invoice Brain. Please reply to this email if you need assistance.
    </div>
</div>
</body>
</html>
