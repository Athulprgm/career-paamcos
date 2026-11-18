<?php

namespace App\Console\Commands;

use App\Mail\TestEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {to? : The email address to send the test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify the SMTP mail delivery system configuration.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = $this->argument('to') ?? config('mail.from.address');

        if (empty($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid recipient email address is required. Provide it as an argument: php artisan mail:test your-email@domain.com');

            return self::FAILURE;
        }

        $this->info('Testing mail delivery configuration...');
        $this->line('Mailer: '.config('mail.default'));
        $this->line('Host: '.config('mail.mailers.smtp.host'));
        $this->line('Port: '.config('mail.mailers.smtp.port'));
        $this->line('Username: '.(config('mail.mailers.smtp.username') ?? '(not set)'));
        $this->line('From: '.config('mail.from.name').' <'.config('mail.from.address').'>');
        $this->line('Sending test email to: '.$recipient);

        try {
            Mail::to($recipient)->send(new TestEmail('Manual delivery test initiated via Artisan console.'));

            $this->newLine();
            $this->info('✓ Test email successfully dispatched to: '.$recipient);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('✗ Failed to send email: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
