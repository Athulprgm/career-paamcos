<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $recipientName,
        public string $accountType,
        public string $loginEmail,
        public string $rawPassword,
        public ?string $loginUrl = null,
        public ?string $identifier = null,
        public ?string $branchName = null,
        public ?string $roleOrRank = null,
        public ?string $customMessage = null
    ) {
        $this->loginUrl = $loginUrl ?: url('/superadmin/login');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name', 'Pristine Agro');
        $subject = "Welcome to {$appName} — Your {$this->accountType} Login Credentials";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome_credentials',
            with: [
                'recipientName' => $this->recipientName,
                'accountType' => $this->accountType,
                'loginEmail' => $this->loginEmail,
                'rawPassword' => $this->rawPassword,
                'loginUrl' => $this->loginUrl,
                'identifier' => $this->identifier,
                'branchName' => $this->branchName,
                'roleOrRank' => $this->roleOrRank,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
