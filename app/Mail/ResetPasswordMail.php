<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;

    public string $loginEmail;

    public string $accountType;

    public int $expireMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $token,
        public string $resetUrl
    ) {
        $this->recipientName = $user->name ?: 'Member';
        $this->loginEmail = $user->email;
        $this->accountType = match (true) {
            $user->isSuperAdmin() => 'Super Admin',
            $user->isBranchAdmin() => 'Branch Admin',
            $user->isAgent() => 'Agent',
            $user->isStaff() => 'Staff',
            default => 'Portal User',
        };
        $this->expireMinutes = (int) config('auth.passwords.users.expire', 60);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name', 'PAAMCO');

        return new Envelope(
            subject: "Reset Your Password — {$appName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reset_password',
            with: [
                'recipientName' => $this->recipientName,
                'loginEmail' => $this->loginEmail,
                'accountType' => $this->accountType,
                'resetUrl' => $this->resetUrl,
                'token' => $this->token,
                'expireMinutes' => $this->expireMinutes,
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
