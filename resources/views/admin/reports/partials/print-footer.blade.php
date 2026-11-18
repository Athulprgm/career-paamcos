<div class="print-coop-footer">
    <!-- Co-operative Operational Sign-off Block -->
    <div class="print-signoff-block" style="margin-top: 22px; page-break-inside: avoid !important; break-inside: avoid !important;">
        <table class="print-signoff-table" style="width: 100%; border: none !important; border-collapse: separate; border-spacing: 12px 0;">
            <tr>
                <td style="width: 33.33%; vertical-align: top; text-align: center; border: 1px dashed #94a3b8 !important; border-radius: 4px; padding: 10px 12px 8px 12px; background: #fafafa !important;">
                    <div style="font-size: 7.2pt; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 30px;">
                        PREPARED BY (MAKER)
                    </div>
                    <div style="border-bottom: 1px solid #334155; width: 85%; margin: 0 auto 4px auto;"></div>
                    <div style="font-size: 7pt; font-weight: 600; color: #0f172a;">
                        {{ auth()->user()->name ?? 'System Operator' }}
                    </div>
                    <div style="font-size: 6.5pt; color: #64748b;">
                        Signature &amp; Date
                    </div>
                </td>
                <td style="width: 33.33%; vertical-align: top; text-align: center; border: 1px dashed #94a3b8 !important; border-radius: 4px; padding: 10px 12px 8px 12px; background: #fafafa !important;">
                    <div style="font-size: 7.2pt; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 30px;">
                        CHECKED &amp; VERIFIED BY (CHECKER)
                    </div>
                    <div style="border-bottom: 1px solid #334155; width: 85%; margin: 0 auto 4px auto;"></div>
                    <div style="font-size: 7pt; font-weight: 600; color: #0f172a;">
                        Accountant / Internal Auditor
                    </div>
                    <div style="font-size: 6.5pt; color: #64748b;">
                        Verification Signature &amp; Date
                    </div>
                </td>
                <td style="width: 33.33%; vertical-align: top; text-align: center; border: 1px dashed #94a3b8 !important; border-radius: 4px; padding: 10px 12px 8px 12px; background: #fafafa !important;">
                    <div style="font-size: 7.2pt; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 30px;">
                        AUTHORIZED SIGNATORY (AUTHORIZER)
                    </div>
                    <div style="border-bottom: 1px solid #334155; width: 85%; margin: 0 auto 4px auto;"></div>
                    <div style="font-size: 7pt; font-weight: 600; color: #0f172a;">
                        Branch Manager / Secretary
                    </div>
                    <div style="font-size: 6.5pt; color: #64748b;">
                        Official Seal &amp; Signature
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Official Dispatch Notice -->
    <div style="margin-top: 14px; padding-top: 6px; border-top: 1px solid #cbd5e1; text-align: center; font-size: 6.8pt; color: #64748b; line-height: 1.4;">
        <span><strong>{{ config('app.name', 'MultiCred') }} Multi-Credit Co-operative Society Limited</strong> &bull; MultiCred Operational Audit Dispatch</span>
        <span class="mx-2">&bull;</span>
        <span>Confidential Internal Financial Record</span>
        <span class="mx-2">&bull;</span>
        <span>System Timestamp: {{ now()->format('d/m/Y H:i:s') }}</span>
    </div>
</div>
