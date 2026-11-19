<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $previousStatus = null
    ) {}

    public function envelope(): Envelope
    {
        $statusFormatted = ucfirst($this->order->status);
        return new Envelope(
            subject: "SHOP.CO Order Update: #{$this->order->order_number} is now {$statusFormatted}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status_updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
