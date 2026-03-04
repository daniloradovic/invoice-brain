<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice, public string $customMessage = '')
    {
        $this->invoice = $invoice->loadMissing(['client', 'lineItems']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->invoice->client->email,
            subject: "Invoice {$this->invoice->invoice_number} from Invoice Brain",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice',
            with: [
                'invoice'       => $this->invoice,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = app(InvoicePdfService::class)->generate($this->invoice);

        return [
            Attachment::fromData(
                fn () => $pdf,
                "{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
