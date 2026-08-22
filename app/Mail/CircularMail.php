<?php

namespace App\Mail;

use App\Models\Circular;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CircularMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Circular $circular,
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Circular] {$this->circular->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.circular',
        );
    }
}
