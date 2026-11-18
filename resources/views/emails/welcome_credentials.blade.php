@extends('emails.layout', ['preheader' => 'Your ' . ($accountType ?? 'Portal') . ' login credentials for ' . config('app.name', 'MultiCred')])

@section('content')
<!-- ONBOARDING STATUS PILL -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
    <tr>
        <td class="status-pill" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #14532D; border-radius: 9999px; padding: 4px 12px; display: inline-block;">
            &bull; Official Onboarding &bull; Portal Access Granted
        </td>
    </tr>
</table>

<!-- HEADLINE -->
<h1 class="title-text" style="margin: 0 0 12px 0; font-size: 22px; font-weight: 700; line-height: 1.25; letter-spacing: -0.02em; color: #0F172A;">
    Welcome to {{ config('app.name', 'MultiCred') }}, {{ $recipientName }}
</h1>

<!-- BODY COPY -->
<p class="body-text" style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #334155;">
    Your <strong>{{ $accountType }}</strong> account has been successfully provisioned within the {{ config('app.name', 'MultiCred') }} Deposit &amp; Commission management platform. Below are your official system credentials to sign in to your dedicated portal.
</p>

@if(!empty($customMessage))
<!-- CUSTOM MESSAGE / NOTICE -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
    <tr>
        <td class="notice-box" style="background-color: #F8FAFC; border: 1px solid #CBD5E1; border-left: 3px solid #C2410C; border-radius: 8px; padding: 14px 18px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="notice-text" style="font-size: 13px; line-height: 1.55; color: #334155;">
                        <strong style="color: #0F172A; font-weight: 600;">Note:</strong> {{ $customMessage }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endif

<!-- CREDENTIALS SPECIFICATIONS CARD -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="spec-container" style="background-color: #FAFAFA; border: 1px solid #CBD5E1; border-radius: 12px; margin-bottom: 28px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
    <tr>
        <td style="padding: 22px 24px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                
                <!-- ROW 1: Login Email & Temporary Password -->
                <tr>
                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-bottom: 16px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748B; margin-bottom: 6px;">
                            Login Email / Username
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
                            Temporary Password
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 700; color: #111827;">
                            <span class="mono-pill" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 14px; letter-spacing: 0.04em; background-color: #FFFFFF; border: 1px solid #111827; border-radius: 6px; padding: 4px 10px; color: #111827; display: inline-block; font-weight: 700;">
                                {{ $rawPassword }}
                            </span>
                        </div>
                    </td>
                </tr>

                <!-- HAIRLINE DIVIDER -->
                <tr>
                    <td colspan="3" class="spec-divider" style="border-top: 1px solid #EAEAEA; padding: 0; font-size: 1px; line-height: 1px;">&nbsp;</td>
                </tr>

                <!-- ROW 2: Account Role & Assigned Branch -->
                <tr>
                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-top: 16px; padding-bottom: 16px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6B7280; margin-bottom: 6px;">
                            Account Role / Designation
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $accountType }} @if(!empty($roleOrRank)) &bull; {{ $roleOrRank }} @endif
                        </div>
                    </td>

                    <td width="4%" class="spec-spacer" style="font-size: 1px; line-height: 1px;">&nbsp;</td>

                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-top: 16px; padding-bottom: 16px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6B7280; margin-bottom: 6px;">
                            Assigned Branch
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $branchName ?: 'Central Administration' }}
                        </div>
                    </td>
                </tr>

                @if(!empty($identifier))
                <!-- HAIRLINE DIVIDER -->
                <tr>
                    <td colspan="3" class="spec-divider" style="border-top: 1px solid #EAEAEA; padding: 0; font-size: 1px; line-height: 1px;">&nbsp;</td>
                </tr>

                <!-- ROW 3: Official Code / Identifier -->
                <tr>
                    <td colspan="3" class="spec-cell spec-cell-last" style="vertical-align: top; padding-top: 16px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748B; margin-bottom: 6px;">
                            Official Reference Identifier
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 600; color: #0F172A;">
                            <span class="mono-pill" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; background-color: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 3px 8px; color: #334155; display: inline-block;">
                                {{ $identifier }}
                            </span>
                        </div>
                    </td>
                </tr>
                @endif

            </table>
        </td>
    </tr>
</table>

<!-- PRIMARY CTA BUTTON -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn-table" style="margin-bottom: 28px;">
    <tr>
        <td>
            <!--[if !mso]><!-->
            <a href="{{ $loginUrl }}" target="_blank" class="cta-btn" style="display: inline-block; background-color: #14532D; color: #FFFFFF; font-size: 13px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                Sign In to Portal &rarr;
            </a>
            <!--<![endif]-->
        </td>
    </tr>
</table>

<!-- CRITICAL SECURITY NOTICE -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
    <tr>
        <td class="notice-box" style="background-color: #F8FAFC; border: 1px solid #CBD5E1; border-left: 3px solid #14532D; border-radius: 8px; padding: 16px 18px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="notice-text" style="font-size: 12px; line-height: 1.6; color: #475569;">
                        <strong style="color: #0F172A; font-weight: 600;">Security Guidelines:</strong>
                        <ol style="margin: 6px 0 0 0; padding-left: 18px;">
                            <li>Sign in using the temporary credentials above and navigate to <strong>Profile Settings</strong> to update your password immediately.</li>
                            <li>Never disclose your login details, temporary password, or OTP to anyone. MultiCred personnel will never ask for your password.</li>
                            <li>Ensure you access the system only via the verified official domain: <code>{{ parse_url($loginUrl, PHP_URL_HOST) ?? url('/') }}</code>.</li>
                        </ol>
                    </td>
                </tr>
            </table>
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
                        System-generated security notification &bull; Encrypted dispatch &bull; MultiCred Multi-State Co-operative Society Ltd.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endsection
