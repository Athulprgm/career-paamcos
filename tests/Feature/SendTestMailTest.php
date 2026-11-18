<?php

namespace Tests\Feature;

use App\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendTestMailTest extends TestCase
{
    public function test_it_sends_test_email_to_specified_recipient(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['to' => 'recipient@example.com'])
            ->expectsOutputToContain('✓ Test email successfully dispatched to: recipient@example.com')
            ->assertSuccessful();

        Mail::assertSent(TestEmail::class, function (TestEmail $mail): bool {
            return $mail->hasTo('recipient@example.com');
        });
    }

    public function test_it_falls_back_to_default_from_address_when_no_argument_provided(): void
    {
        Mail::fake();

        $defaultFrom = (string) config('mail.from.address');

        $this->artisan('mail:test')
            ->expectsOutputToContain('✓ Test email successfully dispatched to: '.$defaultFrom)
            ->assertSuccessful();

        Mail::assertSent(TestEmail::class, function (TestEmail $mail) use ($defaultFrom): bool {
            return $mail->hasTo($defaultFrom);
        });
    }

    public function test_it_fails_when_recipient_is_invalid(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['to' => 'not-an-email'])
            ->expectsOutputToContain('A valid recipient email address is required')
            ->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_it_renders_minimalist_email_preview_successfully(): void
    {
        $response = $this->get(route('mail.preview'));

        $response->assertSuccessful();
        $response->assertSee('Mail Delivery System Verified');
        $response->assertDontSee('System Operational');
        $response->assertSee('Open Platform');
        $response->assertSee('Pristine Agro');
    }
}
