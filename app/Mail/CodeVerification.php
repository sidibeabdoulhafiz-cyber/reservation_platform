<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodeVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->code.' est votre code CotaTrans');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.code-verification', with: [
            'prenom' => (string) str($this->user->name)->before(' '),
            'code' => $this->code,
            'minutes' => config('cotatrans.code_validite_minutes', 10),
            'nouveau' => ! $this->user->email_verified_at,
        ]);
    }
}
