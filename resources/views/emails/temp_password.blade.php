<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>
    NAYSA Financials Cloud –
    {{ ($purpose ?? '') === 'reset'
        ? 'Password Reset'
        : (($purpose ?? '') === 'admin_add' ? 'Account Created' : 'Account Approved') }}
  </title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .preheader {
      display:none !important; visibility:hidden; opacity:0; color:transparent;
      height:0; width:0; overflow:hidden; mso-hide:all;
    }
  </style>
</head>

<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,'Noto Sans',sans-serif;">

@php
  $baseUrl   = rtrim(config('app.url'), '/');
  $purpose   = $purpose ?? '';
  $userQ     = urlencode($userCode ?? '');
  $companyQ  = urlencode($company ?? $companyCode ?? $db ?? $branchcode ?? '');

  $isReset    = $purpose === 'reset';
  $isAdminAdd = $purpose === 'admin_add';
  $isRelease  = $purpose === 'release';

  $actionUrl = "{$baseUrl}/change-password?mode={$purpose}&user={$userQ}&company={$companyQ}";
@endphp

<div class="preheader">
  {{ $isReset
      ? 'Password reset request. Use the secure link to set a new password.'
      : ($isAdminAdd
          ? 'Your account has been created. Temporary password and secure link inside.'
          : 'Your account has been approved. Set your password to continue.') }}
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
  <tr>
    <td align="center" style="padding:24px 12px;">
      <table width="100%" cellpadding="0" cellspacing="0"
             style="max-width:600px;background:#ffffff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;">

        <!-- Header -->
        <tr>
          <td style="background:#2563eb;padding:18px 24px;color:#fff;">
            <table width="100%">
              <tr>
                <td style="font-weight:700;font-size:18px;">NAYSA Financials Cloud</td>
                <td align="right" style="font-size:13px;color:#bfdbfe;">
                  {{ $isReset ? 'Password Reset' : ($isAdminAdd ? 'Account Created' : 'Account Approved') }}
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:28px 24px;color:#111827;font-size:15px;line-height:1.6;">
            <p>Hi <strong>{{ $name }}</strong>,</p>

            @if ($isReset)
              <p>
                We received a request to reset the password for your account
                (User ID: <strong>{{ $userCode }}</strong>).
                Please click the button below to set a new password.
              </p>

            @elseif ($isAdminAdd)
              <p>
                A system administrator has created your
                <strong>NAYSA Financials Cloud</strong> account.
                You may log in using the temporary password below and are required
                to change it immediately.
              </p>

              <table width="100%" style="margin:16px 0;">
                <tr>
                  <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;">
                    <strong>User ID:</strong> {{ $userCode }}<br>
                    <strong>Temporary Password:</strong>
                    <span style="color:#2563eb;font-weight:700">{{ $temp }}</span>
                  </td>
                </tr>
              </table>

            @else {{-- release --}}
              <p>
                Your <strong>NAYSA Financials Cloud account has been approved</strong>.
                Please click the button below to set your password and activate your account.
              </p>
            @endif

            <!-- CTA -->
            <p style="text-align:center;margin:18px 0;">
              <a href="{{ $actionUrl }}"
                 style="background:#2563eb;color:#ffffff;text-decoration:none;
                        padding:12px 20px;border-radius:8px;font-weight:600;">
                {{ $isReset ? 'Reset My Password' : 'Set My Password' }}
              </a>
            </p>

            <!-- Fallback -->
            <p style="font-size:13px;color:#4b5563;">
              If the button doesn’t work, copy and paste this link into your browser:<br>
              <a href="{{ $actionUrl }}" style="color:#2563eb;word-break:break-all;">
                {{ $actionUrl }}
              </a>
            </p>

            <!-- Security -->
            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-top:18px;">
              <strong style="font-size:13px;">Security reminders</strong>
              <ul style="font-size:13px;color:#4b5563;">
                @if ($isAdminAdd)
                  <li>Change your password immediately after logging in.</li>
                @endif
                <li>Use at least 8 characters with uppercase, lowercase, numbers, and symbols.</li>
                <li>NAYSA staff will never ask for your password.</li>
              </ul>
            </div>

            <p style="font-size:12px;color:#6b7280;margin-top:14px;">
              If you did not request this action, please contact your system administrator immediately.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:18px 24px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
            <table width="100%">
              <tr>
                <td>© {{ date('Y') }} NAYSA Financials Cloud</td>
                <td align="right">
                  <a href="https://www.naysasolutions.com/contact-us"
                     style="color:#2563eb;text-decoration:none;">Contact Support</a>
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
