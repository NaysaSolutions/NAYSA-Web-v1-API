<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>NAYSA Financials Cloud – {{ $purpose === 'reset' ? 'Password Reset' : 'Account Approved' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      .preheader {
        display:none !important; visibility:hidden; opacity:0; color:transparent;
        height:0; width:0; overflow:hidden; mso-hide:all;
      }
    </style>
  </head>

  <body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,'Noto Sans',sans-serif;">

    {{-- Preheader (changes per purpose) --}}
    <div class="preheader">
      {{ $purpose === 'reset'
        ? 'Password reset request. Use the secure link to set a new password.'
        : 'Your account has been approved. Temporary password and secure link inside.' }}
    </div>

    @php
  $baseUrl = rtrim(config('app.url'), '/');
  $userQ   = urlencode($userCode ?? '');
  $isReset = ($purpose ?? '') === 'reset';

  // ✅ get company/db code (adjust variable name based on what you pass to the view)
  $companyQ = urlencode($company ?? $companyCode ?? $db ?? $branchcode ?? '');

  // Tokenless URLs (as requested)
  $actionUrl = $isReset
      ? "{$baseUrl}/change-password?mode=reset&user={$userQ}&company={$companyQ}"
      : "{$baseUrl}/change-password?mode=release&user={$userQ}&company={$companyQ}";
@endphp


    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;">
      <tr>
        <td align="center" style="padding:24px 12px;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">

            <!-- Header -->
            <tr>
              <td style="background:#2563eb;padding:18px 24px;color:#fff;">
                <table width="100%" role="presentation" cellspacing="0" cellpadding="0" border="0">
                  <tr>
                    <td style="font-weight:700;font-size:18px;line-height:1.2;">NAYSA Financials Cloud</td>
                    <td align="right" style="font-size:13px;line-height:1.2;color:#bfdbfe;">
                      {{ $isReset ? 'Password Reset' : 'Account Approved' }}
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="padding:28px 24px 10px 24px;color:#111827;font-size:15px;line-height:1.6;">
                <p style="margin:0 0 10px 0;">Hi <strong>{{ $name }}</strong>,</p>

                @if ($isReset)
                  <p style="margin:0 0 14px 0;">
                    We received a request to reset the password for your account (User ID:
                    <strong>{{ $userCode }}</strong>). Click the button below to set a new password.
                  </p>
                @else
                  <p style="margin:0 0 14px 0;">
                    We’re pleased to inform you that your <strong>NAYSA Financials Cloud account has been approved</strong>.
                    You can now log in using the temporary password below and update your password for security.
                  </p>

                  <!-- Info card (only for release/approval) -->
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:16px 0;">
                    <tr>
                      <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;">
                        <table width="100%" role="presentation" cellspacing="0" cellpadding="0" border="0">
                          <tr>
                            <td style="font-weight:600;color:#374151;padding-bottom:6px;">User ID</td>
                            <td align="right" style="font-weight:600;color:#111827;">{{ $userCode }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight:600;color:#374151;padding-top:8px;">Temporary Password</td>
                            <td align="right" style="font-weight:700;color:#2563eb;padding-top:8px;letter-spacing:0.3px;">
                              {{ $temp }}
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                @endif

                <!-- CTA -->
                <p style="margin:16px 0 0 0;text-align:center;">
                  <a href="{{ $actionUrl }}"
                     style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;
                            padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;">
                    {{ $isReset ? 'Reset My Password' : 'Change My Password' }}
                  </a>
                </p>

                <!-- Fallback link -->
                <p style="margin:18px 0 6px 0;color:#4b5563;font-size:13px;">
                  If the button doesn’t work, copy and paste this URL into your browser:
                  <br>
                  <a href="{{ $actionUrl }}" style="color:#2563eb;text-decoration:underline;word-break:break-all;">
                    {{ $actionUrl }}
                  </a>
                </p>

                <!-- Security note -->
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;margin:18px 0;">
                  <p style="margin:0 0 6px 0;font-weight:600;color:#374151;font-size:13px;">Security reminders</p>
                  <ul style="margin:0;padding-left:20px;color:#4b5563;font-size:13px;line-height:1.6;">
                    <li>Change your password immediately after your first login.</li>
                    <li>Use at least 8 characters with uppercase, lowercase, a number, and a symbol.</li>
                    <li>Keep your credentials confidential — NAYSA staff will never ask for your password.</li>
                  </ul>
                </div>

                <p style="margin:14px 0 0 0;color:#6b7280;font-size:12px;">
                  If you did not request this {{ $isReset ? 'password reset' : 'approval' }}, please contact your system administrator immediately.
                </p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="padding:18px 24px;border-top:1px solid #e5e7eb;background:#ffffff;font-size:12px;color:#6b7280;">
                <table width="100%" role="presentation" cellspacing="0" cellpadding="0" border="0">
                  <tr>
                    <td>© {{ date('Y') }} NAYSA Financials Cloud</td>
                    <td align="right">
                      Support • <a href="https://www.naysasolutions.com/contact-us" style="color:#2563eb;text-decoration:none;">Contact us</a>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </body>
</html>




