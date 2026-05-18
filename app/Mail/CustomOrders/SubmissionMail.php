<?php

namespace App\Mail\CustomOrders;

use App\Models\OrderSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email sent to a vendor when a custom-order submission is dispatched.
 *
 * Recipients are resolved from config/custom_orders.php — the vendor's
 * email comes from the provider config, CC comes from env. The from
 * address is Laravel's standard MAIL_FROM_ADDRESS.
 */
class SubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly OrderSubmission $submission) {}

    public function envelope(): Envelope
    {
        $providerEmail = (string) config(
            "custom_orders.providers.{$this->submission->provider_id}.email",
        );

        $cc = (string) config('custom_orders.cc_email');
        $replyTo = (string) config('custom_orders.reply_to');

        return new Envelope(
            to: [new Address($providerEmail)],
            cc: $cc !== '' ? [new Address($cc)] : [],
            replyTo: $replyTo !== '' ? [new Address($replyTo)] : [],
            subject: (string) $this->submission->subject,
        );
    }

    public function content(): Content
    {
        $this->submission->loadMissing('lines.orderLine.snapshot');

        return new Content(
            text: 'emails.custom-orders.submission-text',
            with: [
                'body' => (string) $this->submission->message,
                'lines' => $this->submission->lines,
            ],
        );
    }
}
