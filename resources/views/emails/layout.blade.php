<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title>{{ $subject ?? config('app.name', 'MultiCred') }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style type="text/css">
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        .mso-hide { mso-hide: all !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        /* Client resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* Big Tech Minimalist Typography (Apple, Google, Microsoft, Samsung, Sony) */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #F5F5F7;
            color: #111827;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .cta-btn:hover {
            background-color: #0F3F22 !important;
            opacity: 0.95 !important;
        }

        .footer-link:hover {
            color: #111827 !important;
            text-decoration: underline !important;
        }

        /* Mobile Responsive */
        @media only screen and (max-width: 600px) {
            .email-outer-td {
                padding: 16px 8px 32px 8px !important;
            }
            .email-card {
                width: 100% !important;
                margin: 0 !important;
                border-radius: 12px !important;
            }
            .card-header-inner {
                padding: 28px 20px 20px 20px !important;
            }
            .card-content-inner {
                padding: 24px 20px 32px 20px !important;
            }
            .spec-cell {
                display: block !important;
                width: 100% !important;
                padding-bottom: 14px !important;
            }
            .spec-cell-last {
                padding-bottom: 0 !important;
            }
            .spec-spacer {
                display: none !important;
            }
            .btn-table {
                width: 100% !important;
            }
            .cta-btn {
                display: block !important;
                text-align: center !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
        }

        /* Dark Mode: Apple Mail, iOS, Outlook, Samsung Mail, Gmail Dark Mode */
        @media (prefers-color-scheme: dark) {
            body, .bg-outer {
                background-color: #090F0C !important;
            }
            .email-card {
                background-color: #111A15 !important;
                border-color: #1E2C24 !important;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.45) !important;
            }
            .card-divider {
                border-color: #1E2C24 !important;
            }
            .brand-title {
                color: #FFFFFF !important;
            }
            .brand-subtitle {
                color: #94A3B8 !important;
            }
            .title-text {
                color: #FFFFFF !important;
            }
            .body-text {
                color: #CBD5E1 !important;
            }
            .status-pill {
                background-color: #14532D !important;
                border-color: #10B981 !important;
                color: #FDBA74 !important;
            }
            .notice-box {
                background-color: #18241E !important;
                border-color: #1E2C24 !important;
                border-left-color: #FB923C !important;
            }
            .notice-text {
                color: #E2E8F0 !important;
            }
            .spec-container {
                background-color: #0D1611 !important;
                border-color: #1E2C24 !important;
            }
            .spec-label {
                color: #94A3B8 !important;
            }
            .spec-value {
                color: #FFFFFF !important;
            }
            .mono-pill {
                background-color: #18241E !important;
                border-color: #31453A !important;
                color: #F8FAFC !important;
            }
            .spec-divider {
                border-color: #1E2C24 !important;
            }
            .cta-btn {
                background-color: #059669 !important;
                color: #FFFFFF !important;
            }
            .trust-text {
                color: #94A3B8 !important;
            }
            .footer-text {
                color: #94A3B8 !important;
            }
            .footer-link {
                color: #34D399 !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F5F5F7; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <!-- PREHEADER TEXT (Invisible in email body, visible in inbox list preview) -->
    <div class="mso-hide" style="display: none; font-size: 1px; color: #F5F5F7; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        {{ $preheader ?? 'Operational notification from ' . config('app.name', 'MultiCred') }}
        &zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
    </div>

    <!-- WRAPPER TABLE -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="bg-outer" style="background-color: #F5F5F7; table-layout: fixed;">
        <tr>
            <td align="center" class="email-outer-td" style="padding: 48px 16px 64px 16px;">
                <!--[if mso]>
                <table role="presentation" align="center" border="0" cellspacing="0" cellpadding="0" width="580">
                <tr>
                <td>
                <![endif]-->

                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; margin: 0 auto;">
                    
                    <!-- MAIN CARD: PURE WHITE, AIRY, REFINED BORDER -->
                    <tr>
                        <td>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-card" style="background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
                                
                                <!-- MINIMALIST BRAND MASTHEAD -->
                                <tr>
                                    <td class="card-header-inner" style="padding: 36px 40px 24px 40px; text-align: center;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td align="center">
                                                    @if(file_exists(public_path('logo.png')))
                                                        <a href="{{ config('app.url', url('/')) }}" target="_blank" style="display: inline-block; text-decoration: none;">
                                                            <img src="{{ isset($message) && (!request() || !request()->routeIs('mail.preview')) ? $message->embed(public_path('logo.png')) : asset('logo.png') }}"
                                                                 alt="{{ config('app.name', 'MultiCred') }}"
                                                                 width="44"
                                                                 height="44"
                                                                 style="display: block; width: 44px; height: 44px; max-width: 44px; max-height: 44px; border: 0; outline: none; text-decoration: none; border-radius: 10px; border: 1px solid #E2E8F0;" />
                                                        </a>
                                                    @else
                                                        <div class="brand-monogram" style="width: 44px; height: 44px; line-height: 44px; background-color: #14532D; border-radius: 10px; color: #FFFFFF; font-size: 18px; font-weight: 700; text-align: center; margin: 0 auto; letter-spacing: -0.02em;">
                                                            {{ strtoupper(substr(config('app.name', 'M'), 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding-top: 12px;">
                                                    <div class="brand-title" style="font-size: 14px; font-weight: 700; color: #111827; letter-spacing: -0.01em; line-height: 1.2;">
                                                        {{ config('app.name', 'MultiCred') }}
                                                    </div>
                                                    <div class="brand-subtitle" style="font-size: 10px; font-weight: 600; color: #6B7280; letter-spacing: 0.06em; text-transform: uppercase; margin-top: 3px; line-height: 1.2;">
                                                        Multi-State Co-operative Society Limited
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- DELICATE HAIRLINE DIVIDER -->
                                <tr>
                                    <td style="padding: 0 40px;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td class="card-divider" style="border-top: 1px solid #F3F4F6; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- CARD CONTENT BODY -->
                                <tr>
                                    <td class="card-content-inner" style="padding: 32px 40px 40px 40px;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- MINIMALIST BIG-TECH FOOTER (Quiet, balanced, transparent) -->
                    <tr>
                        <td style="padding: 32px 20px 0 20px; text-align: center;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="font-size: 12px; line-height: 1.6; color: #6B7280;" class="footer-text">
                                        Automated operational transmission &bull; {{ config('app.name', 'MultiCred') }} Deposit & Commission Platform
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 8px; font-size: 11px; line-height: 1.6; color: #9CA3AF;" class="footer-text">
                                        <a href="{{ config('app.url', '#') }}" target="_blank" style="color: #6B7280; text-decoration: none; font-weight: 500;" class="footer-link">Portal</a>
                                        &nbsp;&bull;&nbsp;
                                        <a href="{{ config('app.url', '#') }}" target="_blank" style="color: #6B7280; text-decoration: none; font-weight: 500;" class="footer-link">Security Center</a>
                                        &nbsp;&bull;&nbsp;
                                        <a href="{{ config('app.url', '#') }}" target="_blank" style="color: #6B7280; text-decoration: none; font-weight: 500;" class="footer-link">Help &amp; Support</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 12px; font-size: 11px; line-height: 1.5; color: #9CA3AF;" class="footer-text">
                                        &copy; {{ date('Y') }} MultiCred Multi-State Co-operative Society Limited. All rights reserved.<br />
                                        Please do not reply directly to this automated email.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!--[if mso]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
