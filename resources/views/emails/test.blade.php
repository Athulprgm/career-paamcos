@extends('emails.layout', ['preheader' => 'Operational verification notification from ' . config('app.name')])

@section('content')
<!-- HEADLINE (SF Pro / Google Sans / Segoe UI Clean Heading) -->
<h1 class="title-text" style="margin: 0 0 12px 0; font-size: 24px; font-weight: 700; line-height: 1.25; letter-spacing: -0.03em; color: #111827;">
    Mail Delivery System Verified
</h1>

<!-- BODY COPY -->
<p class="body-text" style="margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; color: #4B5563; letter-spacing: -0.01em;">
    This automated transmission confirms that outbound SMTP delivery for <strong>{{ config('app.name', 'MultiCred') }}</strong> is configured, verified, and operational within the deposit and commission management platform.
</p>

@if(!empty($note))
<!-- MINIMALIST NOTICE CALLOUT (Apple / Samsung One UI style) -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
    <tr>
        <td class="notice-box" style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-left: 3px solid #111827; border-radius: 8px; padding: 14px 18px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="notice-text" style="font-size: 13px; line-height: 1.55; color: #374151;">
                        <strong style="color: #111827; font-weight: 600;">Notice:</strong> {{ $note }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endif

<!-- TECHNICAL SPECIFICATIONS CARD (Sony / Apple / Google Receipt style) -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="spec-container" style="background-color: #FAFAFA; border: 1px solid #F0F0F0; border-radius: 12px; margin-bottom: 28px; overflow: hidden;">
    <tr>
        <td style="padding: 20px 22px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <!-- ROW 1: Protocol & Host -->
                <tr>
                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-bottom: 14px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; margin-bottom: 5px;">
                            Protocol
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 600; color: #111827;">
                            <span class="mono-pill" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; background-color: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 5px; padding: 2px 7px; color: #111827; display: inline-block;">
                                SMTP &bull; Port {{ config('mail.mailers.smtp.port', 587) }}
                            </span>
                        </div>
                    </td>

                    <td width="4%" class="spec-spacer" style="font-size: 1px; line-height: 1px;">&nbsp;</td>

                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-bottom: 14px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; margin-bottom: 5px;">
                            Host
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 600; color: #111827;">
                            <span class="mono-pill" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; background-color: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 5px; padding: 2px 7px; color: #111827; display: inline-block;">
                                {{ config('mail.mailers.smtp.host') }}
                            </span>
                        </div>
                    </td>
                </tr>

                <!-- DIVIDER -->
                <tr>
                    <td colspan="3" class="spec-divider" style="border-top: 1px solid #EAEAEA; padding: 0; font-size: 1px; line-height: 1px;">&nbsp;</td>
                </tr>

                <!-- ROW 2: Sender & Timestamp -->
                <tr>
                    <td width="48%" class="spec-cell" style="vertical-align: top; padding-top: 14px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; margin-bottom: 5px;">
                            Sender
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 500; color: #111827; word-break: break-all;">
                            {{ config('mail.from.address') }}
                        </div>
                    </td>

                    <td width="4%" class="spec-spacer" style="font-size: 1px; line-height: 1px;">&nbsp;</td>

                    <td width="48%" class="spec-cell spec-cell-last" style="vertical-align: top; padding-top: 14px;">
                        <div class="spec-label" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; margin-bottom: 5px;">
                            Dispatched At
                        </div>
                        <div class="spec-value" style="font-size: 13px; font-weight: 500; color: #111827;">
                            {{ now()->format('d M Y') }} &bull; {{ now()->format('H:i:s T') }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- PRIMARY CTA BUTTON (Apple / Microsoft / Google executive minimalist style) -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn-table" style="margin-bottom: 28px;">
    <tr>
        <td>
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ config('app.url', 'http://localhost:8000') }}" style="height:44px;v-text-anchor:middle;width:170px;" arcsize="18%" stroke="f" fillcolor="#14532D">
            <w:anchorlock/>
            <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:600;">Open Platform &rarr;</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ config('app.url', 'http://localhost:8000') }}" target="_blank" class="cta-btn" style="display: inline-block; background-color: #14532D; color: #FFFFFF; font-size: 13px; font-weight: 600; letter-spacing: -0.01em; text-decoration: none; padding: 13px 28px; border-radius: 8px; -webkit-transition: background-color 0.2s ease; transition: background-color 0.2s ease;">
                Open Platform &rarr;
            </a>
            <!--<![endif]-->
        </td>
    </tr>
</table>

<!-- TRUST & SECURITY NOTICE -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="border-top: 1px solid #F3F4F6; padding-top: 16px;" class="card-divider">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="trust-text" style="font-size: 11px; line-height: 1.6; color: #6B7280;">
                        <span style="font-size: 12px; vertical-align: middle; margin-right: 4px;">🔒</span>
                        Authenticated ESMTP transmission &bull; TLS 1.3 encrypted &bull; RFC 5321 verified
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endsection
