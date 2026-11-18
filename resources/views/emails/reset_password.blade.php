@extends('emails.layout', ['preheader' => 'Password reset request for your ' . config('app.name', 'MultiCred') . ' account'])

@section('content')
<!-- SECURITY STATUS PILL -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
    <tr>
        <td class="status-pill" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background-color: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; border-radius: 9999px; padding: 4px 12px; display: inline-block;">
            &bull; Security Notification &bull; Password Reset Request
        </td>
    </tr>
</table>

<!-- HEADLINE -->
<h1 class="title-text" style="margin: 0 0 12px 0; font-size: 22px; font-weight: 700; line-height: 1.25; letter-spacing: -0.02em; color: #0F172A;">
    Reset Your Password
</h1>

<!-- BODY COPY -->
<p class="body-text" style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
    Hello <strong>{{ $recipientName }}</strong>,
</p>
<p class="body-text" style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #334155;">
    We received a request to reset the password for your <strong>{{ $accountType }}</strong> account associated with <strong>{{ $loginEmail }}</strong>. You can reset your password immediately by clicking the button below.
</p>

<!-- PRIMARY CTA BUTTON -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn-table" style="margin-bottom: 28px;">
    <tr>
        <td>
            <a href="{{ $resetUrl }}" target="_blank" class="cta-btn" style="display: inline-block; background-color: #14532D; color: #FFFFFF; font-size: 13px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; text-decoration: none; padding: 13px 30px; border-radius: 8px;">
                Reset Password &rarr;
            </a>
        </td>
    </tr>
</table>

<!-- REQUEST SPECIFICATIONS CARD -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="spec-container" style="background-color: #FAFAFA; border: 1px solid #CBD5E1; border-radius: 12px; margin-bottom: 28px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
    <tr>
        <td style="padding: 22px 24px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                
                <!-- ROW 1: Account Email & Account Type -->
                <tr>
                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-bottom: 16px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748B; margin-bottom: 6px;">
                            Account Email
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 600; color: #0F172A; word-break: break-all;">
                            <span class="mono-pill" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; background-color: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 4px 10px; color: #0F172A; display: inline-block;">
                                {{ $loginEmail }}
                            </span>
                        </div>
                    </td>

                    <td width="4%" class="spec-spacer" style="font-size: 1px; line-height: 1px;">&nbsp;</td>

                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-bottom: 16px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6B7280; margin-bottom: 6px;">
                            Link Validity Window
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 700; color: #111827;">
                            <span class="mono-pill" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; letter-spacing: 0.02em; background-color: #FFFFFF; border: 1px solid #14532D; border-radius: 6px; padding: 4px 10px; color: #14532D; display: inline-block; font-weight: 700;">
                                {{ $expireMinutes }} Minutes
                            </span>
                        </div>
                    </td>
                </tr>

                <!-- HAIRLINE DIVIDER -->
                <tr>
                    <td colspan="3" class="spec-divider" style="border-top: 1px solid #EAEAEA; padding: 0; font-size: 1px; line-height: 1px;">&nbsp;</td>
                </tr>

                <!-- ROW 2: Account Role -->
                <tr>
                    <td colspan="3" class="spec-cell spec-cell-last" style="vertical-align: top; padding-top: 16px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6B7280; margin-bottom: 6px;">
                            Portal Access Level
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $accountType }} &bull; Multi-State Co-operative Portal
                        </div>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

<!-- CRITICAL SECURITY NOTICE -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
    <tr>
        <td class="notice-box" style="background-color: #F8FAFC; border: 1px solid #CBD5E1; border-left: 3px solid #14532D; border-radius: 8px; padding: 16px 18px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="notice-text" style="font-size: 12px; line-height: 1.6; color: #475569;">
                        <strong style="color: #0F172A; font-weight: 600;">Security Guidelines:</strong>
                        <ul style="margin: 6px 0 0 0; padding-left: 18px;">
                            <li>This password reset link is single-use and will automatically expire in <strong>{{ $expireMinutes }} minutes</strong>.</li>
                            <li>If you did not request a password reset, no action is needed. Your existing password remains secure.</li>
                            <li>MultiCred personnel will never ask you for your password or reset link.</li>
                        </ul>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- FALLBACK DIRECT URL -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
    <tr>
        <td style="font-size: 12px; line-height: 1.6; color: #64748B;">
            If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
            <div style="margin-top: 8px; word-break: break-all; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11px; background-color: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 6px; padding: 8px 12px; color: #1E293B;">
                <a href="{{ $resetUrl }}" style="color: #14532D; text-decoration: underline;" target="_blank">{{ $resetUrl }}</a>
            </div>
        </td>
    </tr>
</table>

<!-- TRUST & TRANSMISSION VERIFICATION FOOTER -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="border-top: 1px solid #F3F4F6; padding-top: 16px;" class="card-divider">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="trust-text" style="font-size: 11px; line-height: 1.6; color: #6B7280;">
                        <span style="font-size: 12px; vertical-align: middle; margin-right: 4px;">🔒</span>
                        System-generated security notification &bull; Encrypted dispatch &bull; {{ config('app.name', 'MultiCred') }} Multi-State Co-operative Society Ltd.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endsection
